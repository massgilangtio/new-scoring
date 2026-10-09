import json
import time
import traceback
from typing import Any

from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import JSONResponse, Response

from app.services.system_logger import extract_user_from_auth_header, record_system_log


class SystemLogMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        path = request.url.path

        # Ignore static or health check ping requests if needed
        if path == "/health" or path.startswith("/static") or path == "/favicon.ico":
            return await call_next(request)

        start_time = time.perf_counter()

        # Capture request user from Authorization header if present
        auth_header = request.headers.get("authorization")
        user_id, username = extract_user_from_auth_header(auth_header)

        # Capture client IP & Agent
        client_ip = (
            request.headers.get("x-forwarded-for", "").split(",")[0].strip()
            or request.headers.get("x-real-ip")
            or (request.client.host if request.client else None)
        )
        user_agent = request.headers.get("user-agent")
        query_params = str(request.query_params) if request.query_params else None

        # Capture Request Body safely
        request_body = None
        content_type = request.headers.get("content-type", "")
        if "multipart/form-data" in content_type:
            request_body = {"_type": "multipart_form_data"}
        elif request.method in ("POST", "PUT", "PATCH", "DELETE"):
            try:
                body_bytes = await request.body()
                if body_bytes:
                    try:
                        request_body = json.loads(body_bytes.decode("utf-8"))
                    except Exception:
                        request_body = {"raw": body_bytes.decode("utf-8", errors="replace")[:2000]}
            except Exception:
                pass

        status_code = 500
        error_message = None
        traceback_str = None
        response_body = None
        response = None

        try:
            response = await call_next(request)
            status_code = response.status_code

            # Check if exception handlers recorded error info
            if hasattr(request.state, "error_message"):
                error_message = request.state.error_message
            if hasattr(request.state, "traceback"):
                traceback_str = request.state.traceback

            # Read response body chunks safely
            response_body_bytes = b""
            async for chunk in response.body_iterator:
                response_body_bytes += chunk

            # Reconstruct response so client gets it intact
            response = Response(
                content=response_body_bytes,
                status_code=response.status_code,
                headers=dict(response.headers),
                media_type=response.media_type,
            )

            # Parse JSON response body if applicable
            if response_body_bytes and "application/json" in (response.media_type or ""):
                try:
                    parsed_res = json.loads(response_body_bytes.decode("utf-8"))
                    if isinstance(parsed_res, (dict, list)):
                        response_body = parsed_res
                        if not error_message and isinstance(parsed_res, dict) and status_code >= 400:
                            error_message = parsed_res.get("message")
                except Exception:
                    pass

        except Exception as exc:
            # Fatal unhandled bug / server crash
            status_code = 500
            error_message = f"{exc.__class__.__name__}: {str(exc)}"
            traceback_str = traceback.format_exc()
            response = JSONResponse(
                status_code=500,
                content={"rcode": "99", "message": "Terjadi kesalahan pada layanan", "result": {}},
            )
        finally:
            execution_time_ms = (time.perf_counter() - start_time) * 1000.0

            # If user wasn't in header but resolved during request
            if not username and hasattr(request.state, "username"):
                username = request.state.username
            if not user_id and hasattr(request.state, "user_id"):
                user_id = request.state.user_id

            try:
                record_system_log(
                    status_code=status_code,
                    method=request.method,
                    path=path,
                    execution_time_ms=execution_time_ms,
                    query_params=query_params,
                    client_ip=client_ip,
                    user_agent=user_agent,
                    user_id=user_id,
                    username=username,
                    request_body=request_body,
                    response_body=response_body,
                    error_message=error_message,
                    traceback_str=traceback_str,
                )
            except Exception:
                pass

        return response
