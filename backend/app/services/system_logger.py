import json
import logging
import os
from datetime import datetime
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Any

from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.core.security import PURPOSE_ACCESS, decode_token
from app.db.session import SessionLocal
from app.models.tables import SystemLog, User

# Ensure logs directory exists
LOGS_DIR = Path(__file__).resolve().parents[2] / "logs"
LOGS_DIR.mkdir(parents=True, exist_ok=True)

# Logger setup
logger = logging.getLogger("system_logger")
logger.setLevel(logging.INFO)

if not logger.handlers:
    formatter = logging.Formatter(
        "[%(asctime)s] [%(levelname)s] [%(name)s] %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    # General app/system log file (up to 10MB per file, 5 backups)
    app_handler = RotatingFileHandler(
        LOGS_DIR / "system.log",
        maxBytes=10 * 1024 * 1024,
        backupCount=5,
        encoding="utf-8",
    )
    app_handler.setLevel(logging.INFO)
    app_handler.setFormatter(formatter)
    logger.addHandler(app_handler)

    # Error only log file
    error_handler = RotatingFileHandler(
        LOGS_DIR / "error.log",
        maxBytes=10 * 1024 * 1024,
        backupCount=5,
        encoding="utf-8",
    )
    error_handler.setLevel(logging.WARNING)
    error_handler.setFormatter(formatter)
    logger.addHandler(error_handler)

SENSITIVE_KEYS = {
    "password",
    "pass",
    "secret",
    "token",
    "access_token",
    "mfa_token",
    "refresh_token",
    "authorization",
    "mfa_key",
    "cookie",
}


def sanitize_payload(data: Any) -> Any:
    """Recursively mask sensitive keys in payloads."""
    if isinstance(data, dict):
        sanitized = {}
        for k, v in data.items():
            if str(k).lower() in SENSITIVE_KEYS:
                sanitized[k] = "******"
            elif isinstance(v, (dict, list)):
                sanitized[k] = sanitize_payload(v)
            else:
                sanitized[k] = v
        return sanitized
    elif isinstance(data, list):
        return [sanitize_payload(item) for item in data]
    return data


def extract_user_from_auth_header(auth_header: str | None) -> tuple[int | None, str | None]:
    """Extract user_id and username from Authorization Bearer token without crashing."""
    if not auth_header or not auth_header.lower().startswith("bearer "):
        return None, None
    raw_token = auth_header[7:].strip()
    if not raw_token:
        return None, None

    try:
        user_id = decode_token(raw_token, PURPOSE_ACCESS)
    except Exception:
        return None, None

    # Retrieve username
    try:
        with SessionLocal() as db:
            user = db.get(User, user_id)
            if user:
                return user.id, user.username
    except Exception:
        pass

    return user_id, None


def record_system_log(
    *,
    status_code: int,
    method: str,
    path: str,
    execution_time_ms: float,
    query_params: str | None = None,
    client_ip: str | None = None,
    user_agent: str | None = None,
    user_id: int | None = None,
    username: str | None = None,
    request_body: Any = None,
    response_body: Any = None,
    error_message: str | None = None,
    traceback_str: str | None = None,
) -> None:
    """Save system log entry to file and PostgreSQL database."""
    # Determine level
    if status_code >= 500:
        level = "ERROR"
    elif status_code >= 400:
        level = "WARNING"
    elif status_code >= 200:
        level = "SUCCESS"
    else:
        level = "INFO"

    # Log to python file handler
    log_msg = (
        f"[{level}] {method} {path} - Status: {status_code} ({execution_time_ms:.1f}ms) "
        f"User: {username or 'anonymous'} IP: {client_ip or '-'}"
    )
    if error_message:
        log_msg += f" | Error: {error_message}"

    if level == "ERROR":
        logger.error(log_msg)
        if traceback_str:
            logger.error(f"Traceback:\n{traceback_str}")
    elif level == "WARNING":
        logger.warning(log_msg)
    else:
        logger.info(log_msg)

    # Clean & format request / response body for JSONB storage
    clean_request = sanitize_payload(request_body) if request_body is not None else None
    clean_response = sanitize_payload(response_body) if response_body is not None else None

    # Save to PostgreSQL system_logs table
    try:
        with SessionLocal() as db:
            log_record = SystemLog(
                level=level,
                status_code=status_code,
                method=method[:10],
                path=path[:500],
                query_params=query_params,
                client_ip=client_ip[:50] if client_ip else None,
                user_agent=user_agent,
                user_id=user_id,
                username=username[:100] if username else None,
                execution_time_ms=round(execution_time_ms, 2),
                request_body=clean_request if isinstance(clean_request, (dict, list)) else None,
                response_body=clean_response if isinstance(clean_response, (dict, list)) else None,
                error_message=error_message,
                traceback=traceback_str,
            )
            db.add(log_record)
            db.commit()
    except Exception as db_err:
        logger.error(f"Failed to persist system log to database: {db_err}")
