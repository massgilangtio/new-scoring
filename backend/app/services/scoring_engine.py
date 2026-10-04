from decimal import Decimal

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models.tables import ScoringParameter, ScoringParameterOption, ScoringThreshold, ScoringVersion


class ScoringError(Exception):
    def __init__(self, message: str) -> None:
        self.message = message


def calculate_version(db: Session, version: ScoringVersion, answers: list[tuple[int, int]]) -> dict:
    parameters = db.scalars(
        select(ScoringParameter)
        .where(ScoringParameter.scoring_version_id == version.id)
        .order_by(ScoringParameter.display_order, ScoringParameter.id)
    ).all()
    if not parameters:
        raise ScoringError("Versi scoring belum memiliki parameter")

    expected = {row.id for row in parameters}
    seen: set[int] = set()
    chosen: dict[int, int] = {}
    for parameter_id, option_id in answers:
        if parameter_id in seen:
            raise ScoringError("Setiap parameter hanya boleh satu nilai")
        seen.add(parameter_id)
        chosen[parameter_id] = option_id
    if seen != expected:
        raise ScoringError("Semua parameter scoring wajib diisi")

    lines = []
    total = Decimal("0")
    for parameter in parameters:
        option = db.get(ScoringParameterOption, chosen[parameter.id])
        if option is None or option.scoring_parameter_id != parameter.id:
            raise ScoringError("Nilai tidak termasuk pada parameter tersebut")
        line_score = option.value * parameter.weight
        total += line_score
        lines.append(
            {
                "parameter_name": parameter.name,
                "option_label": option.label,
                "value": format(option.value, "f"),
                "weight": format(parameter.weight, "f"),
                "line_score": format(line_score, "f"),
                "display_order": parameter.display_order,
            }
        )

    thresholds = db.scalars(
        select(ScoringThreshold)
        .where(ScoringThreshold.scoring_version_id == version.id)
        .order_by(ScoringThreshold.display_order, ScoringThreshold.id)
    ).all()
    matches = [
        row
        for row in thresholds
        if total >= row.min_score and (row.max_score is None or total <= row.max_score)
    ]
    if len(matches) != 1:
        raise ScoringError("Skor tidak cocok dengan tepat satu threshold")
    matched = matches[0]
    return {
        "scoring_version_id": version.id,
        "version_status": version.status,
        "total_score": format(total, "f"),
        "threshold_id": matched.id,
        "result_label": matched.result_label,
        "threshold_min": format(matched.min_score, "f"),
        "threshold_max": None if matched.max_score is None else format(matched.max_score, "f"),
        "lines": lines,
    }
