from pydantic import BaseModel, ConfigDict, Field
from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.api.deps import current_user, get_db
from app.api.errors import ApiError
from app.models.tables import ScoringVersion, User
from app.services.authorization import SCORING_CONFIGURE, can_view_score_details, has_permission
from app.services.scoring_engine import ScoringError, calculate_version

router = APIRouter(prefix="/api/v1/scoring", tags=["scoring-engine"])


class AnswerBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    parameter_id: int
    option_id: int


class CalculateBody(BaseModel):
    model_config = ConfigDict(extra="forbid")
    answers: list[AnswerBody] = Field(min_length=1)


def _can_calculate(db: Session, user: User) -> bool:
    return has_permission(db, user, SCORING_CONFIGURE) or has_permission(db, user, "scoring.submit")


@router.post("/versions/{version_id}/calculate")
def calculate(
    version_id: int,
    body: CalculateBody,
    actor: User = Depends(current_user),
    db: Session = Depends(get_db),
) -> dict:
    if not _can_calculate(db, actor):
        raise ApiError(403, "04", "Anda tidak memiliki hak akses")
    version = db.get(ScoringVersion, version_id)
    if version is None:
        raise ApiError(404, "01", "Versi scoring tidak ditemukan")
    try:
        result = calculate_version(db, version, [(row.parameter_id, row.option_id) for row in body.answers])
    except ScoringError as exc:
        raise ApiError(400, "01", exc.message) from exc

    can_view = can_view_score_details(db, actor)
    if not can_view:
        result = {
            "scoring_version_id": result.get("scoring_version_id"),
            "version_status": result.get("version_status"),
            "can_view_score_details": False,
            "lines": [
                {
                    "parameter_name": line.get("parameter_name"),
                    "option_label": line.get("option_label"),
                    "display_order": line.get("display_order"),
                }
                for line in (result.get("lines") or [])
            ],
        }
    else:
        result = {**result, "can_view_score_details": True}

    return {"rcode": "00", "message": "Skor berhasil dihitung", "result": result}
