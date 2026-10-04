from functools import lru_cache
from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict

BACKEND_DIR = Path(__file__).resolve().parents[2]


class Settings(BaseSettings):
    database_url: str
    jwt_secret: str
    mfa_encryption_key: str
    access_token_minutes: int = 480
    mfa_token_minutes: int = 10
    bootstrap_username: str = ""
    bootstrap_password: str = ""
    bootstrap_full_name: str = ""
    bootstrap_role_code: str = ""
    bootstrap_role_name: str = ""
    bootstrap_branch_code: str = ""
    bootstrap_branch_name: str = ""

    # API Service Gateway (Core Banking)
    gateway_host_url: str = ""
    gateway_api_key: str = ""
    gateway_client_id: str = ""
    gateway_client_secret: str = ""
    gateway_channel_id: str = "33"
    gateway_user_gtw: str = "dtigw"
    gateway_signature: str = "CABUinapmSdD1j8lIqo8Qvvc+ksRK2g2qDEUlwUfzCM="

    model_config = SettingsConfigDict(
        env_file=BACKEND_DIR / ".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )


@lru_cache
def get_settings() -> Settings:
    return Settings()
