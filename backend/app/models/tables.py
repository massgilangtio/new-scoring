from datetime import date, datetime
from decimal import Decimal

from sqlalchemy import (
    BigInteger,
    Boolean,
    CheckConstraint,
    Date,
    DateTime,
    ForeignKey,
    Identity,
    Index,
    Integer,
    Numeric,
    SmallInteger,
    String,
    Text,
    UniqueConstraint,
    text,
)
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.orm import Mapped, mapped_column

from app.db.base import Base


def _pk() -> Mapped[int]:
    return mapped_column(BigInteger, Identity(always=True), primary_key=True)


def _created_at() -> Mapped[datetime]:
    return mapped_column(DateTime(timezone=True), nullable=False, server_default=text("now()"))


def _updated_at() -> Mapped[datetime]:
    return mapped_column(DateTime(timezone=True), nullable=False, server_default=text("now()"))


class Branch(Base):
    __tablename__ = "branches"

    id: Mapped[int] = _pk()
    code: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    name: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class Role(Base):
    __tablename__ = "roles"

    id: Mapped[int] = _pk()
    code: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    name: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class Permission(Base):
    __tablename__ = "permissions"

    id: Mapped[int] = _pk()
    code: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    name: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))


class RolePermission(Base):
    __tablename__ = "role_permissions"

    role_id: Mapped[int] = mapped_column(ForeignKey("roles.id", ondelete="CASCADE"), primary_key=True)
    permission_id: Mapped[int] = mapped_column(
        ForeignKey("permissions.id", ondelete="CASCADE"), primary_key=True
    )


class KelompokJabatan(Base):
    """Kelompok Jabatan HRIS (tbl_kel_jabatan) — dihubungkan ke Role untuk otorisasi user."""

    __tablename__ = "tbl_kel_jabatan"

    id: Mapped[int] = _pk()
    id_kel_jabatan: Mapped[str] = mapped_column(String(50), nullable=False)
    nama_kel_jabatan: Mapped[str | None] = mapped_column(String(200))
    role_id: Mapped[int | None] = mapped_column(ForeignKey("roles.id", ondelete="SET NULL"))
    total_pegawai: Mapped[int] = mapped_column(Integer, default=0, server_default=text("0"))
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


JobGroup = KelompokJabatan


class User(Base):
    __tablename__ = "users"
    __table_args__ = (
        CheckConstraint(
            "mfa_enabled = false OR (mfa_secret_encrypted IS NOT NULL AND mfa_confirmed_at IS NOT NULL)",
            name="ck_users_mfa_enabled",
        ),
    )

    id: Mapped[int] = _pk()
    username: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    password_hash: Mapped[str] = mapped_column(Text, nullable=False)
    full_name: Mapped[str] = mapped_column(Text, nullable=False)
    role_id: Mapped[int] = mapped_column(ForeignKey("roles.id", ondelete="RESTRICT"), nullable=False)
    job_group_id: Mapped[int | None] = mapped_column(ForeignKey("tbl_kel_jabatan.id", ondelete="SET NULL"))
    branch_id: Mapped[int] = mapped_column(ForeignKey("branches.id", ondelete="RESTRICT"), nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    mfa_secret_encrypted: Mapped[str | None] = mapped_column(Text)
    mfa_last_step: Mapped[int | None] = mapped_column(BigInteger)
    mfa_enabled: Mapped[bool] = mapped_column(
        Boolean, nullable=False, default=False, server_default=text("false")
    )
    mfa_confirmed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class UserHris(Base):
    __tablename__ = "tbl_userhris"

    userid: Mapped[str] = mapped_column(String(50), primary_key=True)
    npp: Mapped[str | None] = mapped_column(String(50))
    nrik: Mapped[str | None] = mapped_column(String(50))
    nama: Mapped[str | None] = mapped_column(String(200))
    no_hp: Mapped[str | None] = mapped_column(String(50))
    user_email: Mapped[str | None] = mapped_column(String(150))
    id_unit_kerja: Mapped[str | None] = mapped_column(String(50))
    nm_unit_kerja: Mapped[str | None] = mapped_column(String(200))
    branchid: Mapped[str | None] = mapped_column(String(50))
    id_jabatan: Mapped[str | None] = mapped_column(String(50))
    nm_jabatan: Mapped[str | None] = mapped_column(String(200))
    id_kel_jabatan: Mapped[str | None] = mapped_column(String(50))
    nama_kel_jabatan: Mapped[str | None] = mapped_column(String(200))
    created_at: Mapped[datetime | None] = mapped_column(DateTime, server_default=text("CURRENT_TIMESTAMP"))
    updated_at: Mapped[datetime | None] = mapped_column(DateTime, server_default=text("CURRENT_TIMESTAMP"))
    stsauth: Mapped[int] = mapped_column(Integer, default=0, server_default=text("0"))
    secret_key: Mapped[str | None] = mapped_column(Text)
    stsbest: Mapped[int] = mapped_column(Integer, default=0, server_default=text("0"))
    password: Mapped[str | None] = mapped_column(Text)


class ProductType(Base):
    __tablename__ = "product_types"

    id: Mapped[int] = mapped_column(Integer, primary_key=True)
    name: Mapped[str] = mapped_column(Text, nullable=False)


class Product(Base):
    __tablename__ = "products"
    __table_args__ = (CheckConstraint("business_unit IN (0, 1)", name="ck_products_business_unit"),)

    id: Mapped[int] = _pk()
    code: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    name: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    business_unit: Mapped[int] = mapped_column(SmallInteger, nullable=False, server_default=text("0"))
    interest_rate: Mapped[Decimal | None] = mapped_column(Numeric(8, 2))
    product_type_id: Mapped[int | None] = mapped_column(ForeignKey("product_types.id", ondelete="RESTRICT"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class Debtor(Base):
    __tablename__ = "debtors"
    __table_args__ = (CheckConstraint("nik ~ '^[0-9]{16}$'", name="ck_debtors_nik_digits"),)

    id: Mapped[int] = _pk()
    nik: Mapped[str] = mapped_column(String(16), unique=True, nullable=False)
    full_name: Mapped[str] = mapped_column(Text, nullable=False)
    branch_id: Mapped[int] = mapped_column(ForeignKey("branches.id", ondelete="RESTRICT"), nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    cis_id: Mapped[str | None] = mapped_column(Text)
    cif_id: Mapped[str | None] = mapped_column(Text)
    npwp: Mapped[str | None] = mapped_column(Text)
    birth_date: Mapped[date | None] = mapped_column(Date)
    birth_place: Mapped[str | None] = mapped_column(Text)
    mother_name: Mapped[str | None] = mapped_column(Text)
    gender: Mapped[str | None] = mapped_column(Text)
    phone: Mapped[str | None] = mapped_column(Text)
    address: Mapped[str | None] = mapped_column(Text)
    religion: Mapped[str | None] = mapped_column(Text)
    created_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class ScoringVersion(Base):
    __tablename__ = "scoring_versions"
    __table_args__ = (
        UniqueConstraint("product_id", "version_no", name="uq_scoring_versions_product_version"),
        CheckConstraint("status IN ('draft', 'active', 'inactive')", name="ck_scoring_versions_status"),
        CheckConstraint("version_no >= 1", name="ck_scoring_versions_version_no"),
        Index(
            "uq_scoring_versions_one_active",
            "product_id",
            unique=True,
            postgresql_where=text("status = 'active'"),
        ),
    )

    id: Mapped[int] = _pk()
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="RESTRICT"), nullable=False)
    version_no: Mapped[int] = mapped_column(Integer, nullable=False)
    status: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'draft'"))
    activated_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    created_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class ScoringParameter(Base):
    __tablename__ = "scoring_parameters"
    __table_args__ = (CheckConstraint("weight >= 0", name="ck_scoring_parameters_weight"),)

    id: Mapped[int] = _pk()
    scoring_version_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_versions.id", ondelete="RESTRICT"), nullable=False
    )
    name: Mapped[str] = mapped_column(Text, nullable=False)
    weight: Mapped[Decimal] = mapped_column(Numeric(8, 4), nullable=False)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class ScoringParameterOption(Base):
    __tablename__ = "scoring_parameter_options"

    id: Mapped[int] = _pk()
    scoring_parameter_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_parameters.id", ondelete="RESTRICT"), nullable=False
    )
    label: Mapped[str] = mapped_column(Text, nullable=False)
    value: Mapped[Decimal] = mapped_column(Numeric(14, 4), nullable=False)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class ScoringThreshold(Base):
    __tablename__ = "scoring_thresholds"
    __table_args__ = (
        CheckConstraint("max_score IS NULL OR min_score <= max_score", name="ck_scoring_thresholds_range"),
    )

    id: Mapped[int] = _pk()
    scoring_version_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_versions.id", ondelete="RESTRICT"), nullable=False
    )
    min_score: Mapped[Decimal] = mapped_column(Numeric(18, 4), nullable=False)
    max_score: Mapped[Decimal | None] = mapped_column(Numeric(18, 4))
    result_label: Mapped[str] = mapped_column(Text, nullable=False)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class DynamicField(Base):
    __tablename__ = "dynamic_fields"
    __table_args__ = (
        UniqueConstraint("scoring_version_id", "field_key", name="uq_dynamic_fields_version_key"),
        CheckConstraint(
            "field_type IN ('text', 'number', 'date', 'dropdown', 'textarea', 'radio', 'checkbox')",
            name="ck_dynamic_fields_type",
        ),
    )

    id: Mapped[int] = _pk()
    scoring_version_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_versions.id", ondelete="RESTRICT"), nullable=False
    )
    field_key: Mapped[str] = mapped_column(Text, nullable=False)
    label: Mapped[str] = mapped_column(Text, nullable=False)
    field_type: Mapped[str] = mapped_column(Text, nullable=False)
    is_required: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("false"))
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class DynamicFieldOption(Base):
    __tablename__ = "dynamic_field_options"

    id: Mapped[int] = _pk()
    dynamic_field_id: Mapped[int] = mapped_column(
        ForeignKey("dynamic_fields.id", ondelete="RESTRICT"), nullable=False
    )
    label: Mapped[str] = mapped_column(Text, nullable=False)
    value: Mapped[str] = mapped_column(Text, nullable=False)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class ScoringTransaction(Base):
    __tablename__ = "scoring_transactions"
    __table_args__ = (
        CheckConstraint(
            "status IN ('draft', 'submitted', 'waiting_for_approver_assignment', 'waiting_duplicate_approval', 'approved', 'returned', 'rejected')",
            name="ck_scoring_transactions_status",
        ),
        CheckConstraint("current_revision >= 0", name="ck_scoring_transactions_revision"),
        Index("ix_scoring_transactions_debtor_product", "debtor_id", "product_id"),
        Index("ix_scoring_transactions_branch_status", "branch_id", "status"),
    )

    id: Mapped[int] = _pk()
    transaction_no: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    debtor_id: Mapped[int] = mapped_column(ForeignKey("debtors.id", ondelete="RESTRICT"), nullable=False)
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="RESTRICT"), nullable=False)
    scoring_version_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_versions.id", ondelete="RESTRICT"), nullable=False
    )
    branch_id: Mapped[int] = mapped_column(ForeignKey("branches.id", ondelete="RESTRICT"), nullable=False)
    branchid: Mapped[str | None] = mapped_column(Text, nullable=True)
    status: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'draft'"))
    duplicate_reason: Mapped[str | None] = mapped_column(Text, nullable=True)
    created_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    assigned_approver_id: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"))
    duplicated_from_id: Mapped[int | None] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="RESTRICT")
    )
    current_revision: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class ScoringInput(Base):
    __tablename__ = "scoring_inputs"
    __table_args__ = (
        UniqueConstraint("transaction_id", "scoring_parameter_id", name="uq_scoring_inputs_transaction_parameter"),
    )

    id: Mapped[int] = _pk()
    transaction_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="CASCADE"), nullable=False
    )
    scoring_parameter_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_parameters.id", ondelete="RESTRICT"), nullable=False
    )
    scoring_parameter_option_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_parameter_options.id", ondelete="RESTRICT"), nullable=False
    )


class DynamicFieldInput(Base):
    __tablename__ = "dynamic_field_inputs"
    __table_args__ = (
        UniqueConstraint("transaction_id", "dynamic_field_id", name="uq_dynamic_field_inputs_transaction_field"),
    )

    id: Mapped[int] = _pk()
    transaction_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="CASCADE"), nullable=False
    )
    dynamic_field_id: Mapped[int] = mapped_column(
        ForeignKey("dynamic_fields.id", ondelete="RESTRICT"), nullable=False
    )
    value_text: Mapped[str | None] = mapped_column(Text)


class DynamicFieldInputChoice(Base):
    __tablename__ = "dynamic_field_input_choices"

    dynamic_field_input_id: Mapped[int] = mapped_column(
        ForeignKey("dynamic_field_inputs.id", ondelete="CASCADE"), primary_key=True
    )
    dynamic_field_option_id: Mapped[int] = mapped_column(
        ForeignKey("dynamic_field_options.id", ondelete="RESTRICT"), primary_key=True
    )


class ScoringSnapshot(Base):
    __tablename__ = "scoring_snapshots"
    __table_args__ = (
        UniqueConstraint("transaction_id", "revision_no", name="uq_scoring_snapshots_transaction_revision"),
        CheckConstraint("revision_no >= 1", name="ck_scoring_snapshots_revision"),
    )

    id: Mapped[int] = _pk()
    transaction_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="RESTRICT"), nullable=False
    )
    revision_no: Mapped[int] = mapped_column(Integer, nullable=False)
    scoring_version_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_versions.id", ondelete="RESTRICT"), nullable=False
    )
    total_score: Mapped[Decimal] = mapped_column(Numeric(18, 4), nullable=False)
    threshold_id: Mapped[int | None] = mapped_column(ForeignKey("scoring_thresholds.id", ondelete="RESTRICT"))
    result_label: Mapped[str] = mapped_column(Text, nullable=False)
    threshold_min: Mapped[Decimal] = mapped_column(Numeric(18, 4), nullable=False)
    threshold_max: Mapped[Decimal | None] = mapped_column(Numeric(18, 4))
    calculated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), nullable=False, server_default=text("now()")
    )
    calculated_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)


class ScoringSnapshotLine(Base):
    __tablename__ = "scoring_snapshot_lines"

    id: Mapped[int] = _pk()
    snapshot_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_snapshots.id", ondelete="RESTRICT"), nullable=False
    )
    parameter_name: Mapped[str] = mapped_column(Text, nullable=False)
    option_label: Mapped[str] = mapped_column(Text, nullable=False)
    value: Mapped[Decimal] = mapped_column(Numeric(14, 4), nullable=False)
    weight: Mapped[Decimal] = mapped_column(Numeric(8, 4), nullable=False)
    line_score: Mapped[Decimal] = mapped_column(Numeric(18, 4), nullable=False)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class DynamicFieldSnapshotLine(Base):
    __tablename__ = "dynamic_field_snapshot_lines"

    id: Mapped[int] = _pk()
    snapshot_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_snapshots.id", ondelete="RESTRICT"), nullable=False
    )
    field_label: Mapped[str] = mapped_column(Text, nullable=False)
    field_type: Mapped[str] = mapped_column(Text, nullable=False)
    value_text: Mapped[str | None] = mapped_column(Text)
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class ApprovalDecision(Base):
    __tablename__ = "approval_decisions"
    __table_args__ = (
        UniqueConstraint("transaction_id", "revision_no", name="uq_approval_decisions_transaction_revision"),
        CheckConstraint("revision_no >= 1", name="ck_approval_decisions_revision"),
        CheckConstraint("length(btrim(note)) > 0", name="ck_approval_decisions_note"),
        CheckConstraint("decision IN ('approved', 'rejected', 'returned')", name="ck_approval_decisions_decision"),
    )

    id: Mapped[int] = _pk()
    transaction_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="RESTRICT"), nullable=False
    )
    revision_no: Mapped[int] = mapped_column(Integer, nullable=False)
    approver_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    decision: Mapped[str] = mapped_column(Text, nullable=False)
    note: Mapped[str] = mapped_column(Text, nullable=False)
    decided_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), nullable=False, server_default=text("now()")
    )


class ApproverAssignment(Base):
    __tablename__ = "approver_assignments"
    __table_args__ = (
        CheckConstraint("revision_no >= 1", name="ck_approver_assignments_revision"),
        Index("ix_approver_assignments_transaction_revision", "transaction_id", "revision_no"),
    )

    id: Mapped[int] = _pk()
    transaction_id: Mapped[int] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="RESTRICT"), nullable=False
    )
    revision_no: Mapped[int] = mapped_column(Integer, nullable=False)
    approver_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    reason: Mapped[str | None] = mapped_column(Text)
    assigned_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    assigned_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), nullable=False, server_default=text("now()")
    )


class RescoreRequest(Base):
    __tablename__ = "rescore_requests"
    __table_args__ = (
        CheckConstraint("length(btrim(reason)) > 0", name="ck_rescore_requests_reason"),
        CheckConstraint(
            "status IN ('waiting_approval', 'approved', 'consumed')",
            name="ck_rescore_requests_status",
        ),
        Index("ix_rescore_requests_debtor_product_status", "debtor_id", "product_id", "status"),
    )

    id: Mapped[int] = _pk()
    debtor_id: Mapped[int] = mapped_column(ForeignKey("debtors.id", ondelete="RESTRICT"), nullable=False)
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="RESTRICT"), nullable=False)
    branch_id: Mapped[int] = mapped_column(ForeignKey("branches.id", ondelete="RESTRICT"), nullable=False)
    reason: Mapped[str] = mapped_column(Text, nullable=False)
    status: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'waiting_approval'"))
    requested_by: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    requested_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), nullable=False, server_default=text("now()")
    )
    approved_by: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"))
    approved_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    consumed_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    consumed_transaction_id: Mapped[int | None] = mapped_column(
        ForeignKey("scoring_transactions.id", ondelete="RESTRICT"), unique=True
    )


class SystemSetting(Base):
    __tablename__ = "system_settings"

    setting_key: Mapped[str] = mapped_column(Text, primary_key=True)
    setting_value: Mapped[str] = mapped_column(Text, nullable=False)
    updated_by: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"))
    updated_at: Mapped[datetime] = _updated_at()


class Notification(Base):
    __tablename__ = "notifications"
    __table_args__ = (
        CheckConstraint(
            "event_type IN ('sent_to_approver', 'approved', 'returned', 'rejected', 'duplicate_approved', 'duplicate_rejected')",
            name="ck_notifications_event_type",
        ),
        CheckConstraint("channel = 'in_app'", name="ck_notifications_channel"),
    )

    id: Mapped[int] = _pk()
    recipient_user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    event_type: Mapped[str] = mapped_column(Text, nullable=False)
    channel: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'in_app'"))
    title: Mapped[str] = mapped_column(Text, nullable=False)
    body: Mapped[str] = mapped_column(Text, nullable=False)
    object_type: Mapped[str] = mapped_column(Text, nullable=False)
    object_id: Mapped[int] = mapped_column(BigInteger, nullable=False)
    read_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True))
    created_at: Mapped[datetime] = _created_at()


class AuditLog(Base):
    __tablename__ = "audit_logs"
    __table_args__ = (
        Index("ix_audit_logs_object", "object_type", "object_id"),
        Index("ix_audit_logs_occurred_at", "occurred_at"),
    )

    id: Mapped[int] = _pk()
    actor_user_id: Mapped[int] = mapped_column(ForeignKey("users.id", ondelete="RESTRICT"), nullable=False)
    actor_role_id: Mapped[int] = mapped_column(ForeignKey("roles.id", ondelete="RESTRICT"), nullable=False)
    actor_role_name: Mapped[str] = mapped_column(Text, nullable=False)
    actor_branch_id: Mapped[int] = mapped_column(ForeignKey("branches.id", ondelete="RESTRICT"), nullable=False)
    occurred_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), nullable=False, server_default=text("now()")
    )
    action: Mapped[str] = mapped_column(Text, nullable=False)
    object_type: Mapped[str] = mapped_column(Text, nullable=False)
    object_id: Mapped[str] = mapped_column(Text, nullable=False)
    before_data: Mapped[dict | None] = mapped_column(JSONB)
    after_data: Mapped[dict | None] = mapped_column(JSONB)
    reason: Mapped[str | None] = mapped_column(Text)


class MasterParameter(Base):
    __tablename__ = "master_parameters"

    id: Mapped[int] = _pk()
    name: Mapped[str] = mapped_column(Text, nullable=False)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class MasterSubParameter(Base):
    __tablename__ = "master_sub_parameters"

    id: Mapped[int] = _pk()
    parameter_id: Mapped[int] = mapped_column(ForeignKey("master_parameters.id", ondelete="CASCADE"), nullable=False)
    code: Mapped[str] = mapped_column(Text, nullable=False)
    description: Mapped[str] = mapped_column(Text, nullable=False)
    weight: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    value: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    total: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False, server_default=text("0"))
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class ProductParameterMapping(Base):
    __tablename__ = "product_parameter_mappings"

    id: Mapped[int] = _pk()
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="RESTRICT"), nullable=False)
    version_name: Mapped[str] = mapped_column(Text, nullable=False)
    passing_score: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("350.00"))
    attachment_path: Mapped[str | None] = mapped_column(Text)
    attachment_name: Mapped[str | None] = mapped_column(Text)
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, server_default=text("true"))
    created_by: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="SET NULL"))
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class ProductParameterMappingItem(Base):
    __tablename__ = "product_parameter_mapping_items"

    id: Mapped[int] = _pk()
    mapping_id: Mapped[int] = mapped_column(ForeignKey("product_parameter_mappings.id", ondelete="CASCADE"), nullable=False)
    parameter_id: Mapped[int | None] = mapped_column(ForeignKey("master_parameters.id", ondelete="SET NULL"))
    parameter_name: Mapped[str] = mapped_column(Text, nullable=False)
    code: Mapped[str] = mapped_column(Text, nullable=False)
    description: Mapped[str] = mapped_column(Text, nullable=False)
    weight: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    value: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    total: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False, server_default=text("0"))
    display_order: Mapped[int] = mapped_column(Integer, nullable=False, server_default=text("0"))


class CreditScoring(Base):
    __tablename__ = "credit_scorings"

    id: Mapped[int] = _pk()
    scoring_no: Mapped[str] = mapped_column(Text, unique=True, nullable=False)
    debtor_id: Mapped[int] = mapped_column(ForeignKey("debtors.id", ondelete="RESTRICT"), nullable=False)
    product_id: Mapped[int] = mapped_column(ForeignKey("products.id", ondelete="RESTRICT"), nullable=False)
    mapping_id: Mapped[int | None] = mapped_column(ForeignKey("product_parameter_mappings.id", ondelete="SET NULL"))
    total_score: Mapped[Decimal] = mapped_column(Numeric(14, 2), nullable=False, server_default=text("0"))
    passing_score: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("350.00"))
    eligibility_status: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'TIDAK LAYAK'"))
    status: Mapped[str] = mapped_column(Text, nullable=False, server_default=text("'draft'"))
    duplicate_reason: Mapped[str | None] = mapped_column(Text, nullable=True)
    supervisor_id: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="SET NULL"))
    notes: Mapped[str | None] = mapped_column(Text)
    created_by: Mapped[int | None] = mapped_column(ForeignKey("users.id", ondelete="SET NULL"))
    branch_id: Mapped[int | None] = mapped_column(ForeignKey("branches.id", ondelete="SET NULL"))
    branchid: Mapped[str | None] = mapped_column(Text, nullable=True)
    created_at: Mapped[datetime] = _created_at()
    updated_at: Mapped[datetime] = _updated_at()


class CreditScoringDetail(Base):
    __tablename__ = "credit_scoring_details"

    id: Mapped[int] = _pk()
    scoring_id: Mapped[int] = mapped_column(ForeignKey("credit_scorings.id", ondelete="CASCADE"), nullable=False)
    parameter_name: Mapped[str] = mapped_column(Text, nullable=False)
    sub_parameter_code: Mapped[str] = mapped_column(Text, nullable=False)
    sub_parameter_desc: Mapped[str] = mapped_column(Text, nullable=False)
    weight: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    value: Mapped[Decimal] = mapped_column(Numeric(10, 2), nullable=False, server_default=text("0"))
    total: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False, server_default=text("0"))

