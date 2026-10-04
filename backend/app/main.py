from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException
from sqlalchemy import text
from sqlalchemy.exc import SQLAlchemyError

from app.api.errors import ApiError
from app.api.routes.access import router as access_router
from app.api.routes.audit import router as audit_router
from app.api.routes.auth import router as auth_router
from app.api.routes.dashboard import router as dashboard_router
from app.api.routes.master import router as master_router
from app.api.routes.scoring_config import router as scoring_router
from app.api.routes.scoring_engine import router as scoring_engine_router
from app.api.routes.approvals import router as approval_router
from app.api.routes.notifications import router as notification_router
from app.api.routes.reports import router as report_router
from app.api.routes.rescore import router as rescore_router
from app.api.routes.transactions import router as transaction_router
from app.api.routes.scoring_parameters import router as scoring_params_router
from app.db.session import engine

app = FastAPI(title="New Credit Score", version="0.1.0", docs_url=None, redoc_url=None, openapi_url=None)
app.include_router(auth_router)
app.include_router(audit_router)
app.include_router(dashboard_router)
app.include_router(access_router)
app.include_router(master_router)
app.include_router(scoring_router)
app.include_router(scoring_engine_router)
app.include_router(scoring_params_router)
app.include_router(transaction_router)
app.include_router(approval_router)
app.include_router(rescore_router)
app.include_router(report_router)
app.include_router(notification_router)


@app.exception_handler(ApiError)
def api_error_handler(_request: Request, exc: ApiError) -> JSONResponse:
    return JSONResponse(
        status_code=exc.status_code,
        content={"rcode": exc.rcode, "message": exc.message, "result": {}},
    )


@app.exception_handler(StarletteHTTPException)
def http_error_handler(_request: Request, exc: StarletteHTTPException) -> JSONResponse:
    if exc.status_code == 404:
        message = "Endpoint tidak ditemukan"
        rcode = "01"
    elif exc.status_code == 405:
        message = "Metode tidak didukung"
        rcode = "01"
    else:
        message = "Permintaan tidak dapat diproses"
        rcode = "99"
    return JSONResponse(status_code=exc.status_code, content={"rcode": rcode, "message": message, "result": {}})


@app.exception_handler(Exception)
def unhandled_handler(_request: Request, _exc: Exception) -> JSONResponse:
    return JSONResponse(
        status_code=500,
        content={"rcode": "99", "message": "Terjadi kesalahan pada layanan", "result": {}},
    )


@app.exception_handler(RequestValidationError)
def validation_handler(_request: Request, _exc: RequestValidationError) -> JSONResponse:
    return JSONResponse(
        status_code=400,
        content={"rcode": "01", "message": "Data permintaan tidak lengkap", "result": {}},
    )


@app.get("/health")
def health() -> dict:
    try:
        with engine.connect() as connection:
            connection.execute(text("SELECT 1"))
    except SQLAlchemyError:
        return {
            "rcode": "99",
            "message": "Database tidak tersedia",
            "result": {"database": "unavailable"},
        }
    return {
        "rcode": "00",
        "message": "Service aktif",
        "result": {"database": "ok"},
    }
