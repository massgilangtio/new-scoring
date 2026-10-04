"""Load branch, product, and debtor master data from the docs SQL dumps."""
from __future__ import annotations

import re
from datetime import date
from pathlib import Path

from sqlalchemy import text

from app.db.session import SessionLocal

DOCS = Path(__file__).resolve().parents[2] / "docs"
LEGACY_DEBTOR_COLUMNS = (
    "cisid", "tipeid", "norek", "cifid", "nik", "npwp", "no_kk", "nama", "tgl_lahir",
    "tempat_lahir", "nama_ibu_kandung", "jenkelid", "agamaid", "stsnikahid", "phonenbr",
    "email", "vrfy_email", "vrfy_phonenbr", "alamat", "provinsiid", "kabkotaid",
    "kecamatanid", "kelurahanid", "kode_pos", "alamat_domisili", "provinsiid_domisili",
    "kabkotaid_domisili", "kecamatanid_domisili", "kelurahanid_domisili", "kode_pos_domisili",
    "pekerjaanid", "tgl_mulai_kerja", "stsaktif_kerja", "bidangusahaid", "jabatanid",
    "nama_perusahaan", "alamat_perusahaan", "provinsiid_perusahaan", "kabkotaid_perusahaan",
    "sumberdanaid", "penghasilanid", "nik_pasangan", "nama_pasangan", "tgl_lahir_pasangan",
    "tmpt_lahir_pasangan", "pekerjaanid_pasangan", "sts_verify", "photo", "password",
    "created_at", "updated_at", "cifid_sy", "kls_periodeid",
)


def statements(sql: str) -> list[str]:
    parts: list[str] = []
    current: list[str] = []
    quote = False
    index = 0
    while index < len(sql):
        char = sql[index]
        if char == "'":
            if quote and index + 1 < len(sql) and sql[index + 1] == "'":
                current.append("''")
                index += 2
                continue
            quote = not quote
        if char == ";" and not quote:
            statement = _strip_leading_comments("".join(current))
            if statement:
                parts.append(statement)
            current = []
            index += 1
            continue
        current.append(char)
        index += 1
    tail = _strip_leading_comments("".join(current))
    if tail:
        parts.append(tail)
    return parts


def _strip_leading_comments(statement: str) -> str:
    lines = [line for line in statement.splitlines() if not line.strip().startswith("--")]
    return "\n".join(lines).strip()


def run_file(db, path: Path, replacements: dict[str, str] | None = None) -> None:
    sql = path.read_text(encoding="utf-8")
    for old, new in (replacements or {}).items():
        sql = sql.replace(old, new)
    cursor = db.connection().connection.cursor()
    for statement in statements(sql):
        cursor.execute(statement)


def blank(value) -> str | None:
    if value is None:
        return None
    text_value = str(value).strip()
    return text_value or None


def parse_date(value) -> date | None:
    text_value = blank(value)
    if text_value is None:
        return None
    return date.fromisoformat(text_value[:10])


LOCKED_TABLES = (
    "scoring_inputs",
    "dynamic_field_inputs",
    "dynamic_field_input_choices",
    "scoring_snapshots",
    "scoring_snapshot_lines",
    "dynamic_field_snapshot_lines",
    "approval_decisions",
    "approver_assignments",
    "scoring_transactions",
    "scoring_versions",
    "scoring_parameters",
    "scoring_parameter_options",
    "scoring_thresholds",
    "dynamic_fields",
    "dynamic_field_options",
)


def clear_operational_data(db) -> None:
    for table in LOCKED_TABLES:
        db.execute(text(f"ALTER TABLE {table} DISABLE TRIGGER USER"))
    try:
        for table in (
            "dynamic_field_input_choices",
            "dynamic_field_inputs",
            "scoring_inputs",
            "dynamic_field_snapshot_lines",
            "scoring_snapshot_lines",
            "scoring_snapshots",
            "approval_decisions",
            "approver_assignments",
            "notifications",
            "rescore_requests",
            "scoring_transactions",
            "dynamic_field_options",
            "dynamic_fields",
            "scoring_parameter_options",
            "scoring_parameters",
            "scoring_thresholds",
            "scoring_versions",
            "debtors",
            "products",
        ):
            db.execute(text(f"DELETE FROM {table}"))
    finally:
        for table in LOCKED_TABLES:
            db.execute(text(f"ALTER TABLE {table} ENABLE TRIGGER USER"))


def main() -> None:
    db = SessionLocal()
    try:
        db.execute(text("CREATE SEQUENCE IF NOT EXISTS cfg_produk_id_seq"))
        run_file(db, DOCS / "cfg_branch.sql")
        run_file(db, DOCS / "cfg_produk.sql")
        run_file(db, DOCS / "cfg_jnsproduk.sql")
        run_file(db, DOCS / "cfg_jenkel.sql")
        run_file(db, DOCS / "cfg_agama.sql")
        columns = ", ".join(f'"{name}" text' for name in LEGACY_DEBTOR_COLUMNS)
        db.execute(text("DROP TABLE IF EXISTS legacy_debitur"))
        db.execute(text(f"CREATE TABLE legacy_debitur ({columns})"))
        run_file(db, DOCS / "tbl_debitur.sql", {'"<table_name>"': "legacy_debitur"})

        db.execute(
            text(
                """
                INSERT INTO product_types (id, name)
                SELECT jnsprodukid, COALESCE(NULLIF(btrim(jnsproduknm), ''), 'Tipe ' || jnsprodukid)
                FROM cfg_jnsproduk
                WHERE jnsprodukid NOT IN (11, 12)
                ON CONFLICT (id) DO UPDATE
                SET name = EXCLUDED.name
                """
            )
        )

        db.execute(
            text(
                """
                INSERT INTO branches (code, name, is_active)
                SELECT branchid, COALESCE(NULLIF(btrim(branchnm), ''), branchid), COALESCE(stsaktif, 0) = 1
                FROM cfg_branch
                ON CONFLICT (code) DO UPDATE
                SET name = EXCLUDED.name, is_active = EXCLUDED.is_active, updated_at = now()
                """
            )
        )
        head_office = db.execute(text("SELECT id FROM branches WHERE code = '001'")).scalar_one()
        db.execute(
            text(
                """
                UPDATE users
                SET branch_id = :office
                WHERE branch_id IN (
                    SELECT id FROM branches
                    WHERE code NOT IN (SELECT branchid FROM cfg_branch)
                )
                """
            ),
            {"office": head_office},
        )
        clear_operational_data(db)
        db.execute(text("ALTER TABLE audit_logs DISABLE TRIGGER USER"))
        try:
            db.execute(
                text(
                    """
                    UPDATE audit_logs
                    SET actor_branch_id = :office
                    WHERE actor_branch_id IN (
                        SELECT id FROM branches
                        WHERE code NOT IN (SELECT branchid FROM cfg_branch)
                    )
                    """
                ),
                {"office": head_office},
            )
        finally:
            db.execute(text("ALTER TABLE audit_logs ENABLE TRIGGER USER"))
        db.execute(
            text(
                """
                DELETE FROM branches
                WHERE code NOT IN (SELECT branchid FROM cfg_branch)
                """
            )
        )

        rows = db.execute(
            text(
                """
                SELECT produkid, produknm, stsbranch, stsaktif, jnsprodukid, intrate,
                       COUNT(*) OVER (PARTITION BY produkid) AS copies
                FROM cfg_produk
                """
            )
        ).all()
        seen: set[str] = set()
        for row in rows:
            code = row.produkid if row.copies == 1 else f"{row.produkid}-{row.stsbranch}"
            if code in seen:
                code = f"{code}-{len(seen)}"
            seen.add(code)
            name = blank(row.produknm) or code
            db.execute(
                text(
                    """
                    INSERT INTO products (code, name, is_active, business_unit, product_type_id, interest_rate)
                    VALUES (:code, :name, :active, :unit, :type_id, :rate)
                    """
                ),
                {
                    "code": code,
                    "name": name,
                    "active": int(row.stsaktif or 0) == 1,
                    "unit": 1 if int(row.stsbranch or 0) == 1 else 0,
                    "type_id": row.jnsprodukid,
                    "rate": row.intrate,
                },
            )

        admin_id = db.execute(text("SELECT id FROM users WHERE username = 'admin.ti'")).scalar_one()
        people = db.execute(
            text(
                """
                SELECT d.cisid, d.cifid, d.nik, d.npwp, d.nama, d.tgl_lahir, d.tempat_lahir,
                       d.nama_ibu_kandung, j.jenkelnm, d.phonenbr, d.alamat, a.agamanm
                FROM legacy_debitur d
                LEFT JOIN cfg_jenkel j ON j.jenkelid::text = d.jenkelid
                LEFT JOIN cfg_agama a ON a.agamaid::text = d.agamaid
                """
            )
        ).all()
        kept = skipped = 0
        for person in people:
            nik = re.sub(r"\D", "", blank(person.nik) or "")
            name = blank(person.nama)
            if name is None or not re.fullmatch(r"\d{16}", nik):
                skipped += 1
                continue
            db.execute(
                text(
                    """
                    INSERT INTO debtors (
                        nik, full_name, branch_id, is_active, created_by,
                        cis_id, cif_id, npwp, birth_date, birth_place, mother_name,
                        gender, phone, address, religion
                    ) VALUES (
                        :nik, :name, :branch_id, true, :created_by,
                        :cis_id, :cif_id, :npwp, :birth_date, :birth_place, :mother_name,
                        :gender, :phone, :address, :religion
                    )
                    """
                ),
                {
                    "nik": nik,
                    "name": name,
                    "branch_id": head_office,
                    "created_by": admin_id,
                    "cis_id": blank(person.cisid),
                    "cif_id": blank(person.cifid),
                    "npwp": blank(person.npwp),
                    "birth_date": parse_date(person.tgl_lahir),
                    "birth_place": blank(person.tempat_lahir),
                    "mother_name": blank(person.nama_ibu_kandung),
                    "gender": blank(person.jenkelnm),
                    "phone": blank(person.phonenbr),
                    "address": blank(person.alamat),
                    "religion": blank(person.agamanm),
                },
            )
            kept += 1
        counts = db.execute(
            text(
                """
                SELECT 'branches' AS name, count(*) FROM branches
                UNION ALL SELECT 'products', count(*) FROM products
                UNION ALL SELECT 'debtors', count(*) FROM debtors
                """
            )
        ).all()
        db.commit()
        print({row.name: row.count for row in counts}, {"debtors_kept": kept, "debtors_skipped": skipped})
    except Exception:
        db.rollback()
        raise
    finally:
        db.close()


if __name__ == "__main__":
    main()
