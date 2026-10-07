from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter(prefix="/mock-gateway", tags=["mock-gateway"])


class HrisAuthRequest(BaseModel):
    reqid: str
    userId: str
    password: str


@router.post("/hris/authLogin")
def mock_hris_auth_login(body: HrisAuthRequest) -> dict:
    if body.reqid != "HR001":
        return {"rcode": "01", "message": "Kode request (reqid) tidak sesuai", "result": {}}

    # Dukung username 4259 dan password P@ssw0rd (termasuk ciphertext dari koleksi Postman/Hoppscotch)
    valid_users = {
        "4259": "P@ssw0rd",
        "Mjdocm9jUUJISjNUM0lTaU5TdDdNRG95dHBacmVMZW5ROVFUanRLbkRWcz0=": "VG1PL050NStYTjJ2NFlXSkNzNCsyU25SeHA0QThwWTE4SENJUVYxUlFLST0=",
    }

    is_valid = False
    if body.userId in valid_users:
        expected = valid_users[body.userId]
        if body.password in (expected, "P@ssw0rd", "VG1PL050NStYTjJ2NFlXSkNzNCsyU25SeHA0QThwWTE4SENJUVYxUlFLST0="):
            is_valid = True

    if is_valid:
        return {
            "rcode": "00",
            "message": "Autentikasi HRIS Berhasil",
            "result": {
                "userId": "4259",
                "full_name": "Pegawai DTI 4259",
                "unitKerjaId": "665",
                "unitKerjaName": "Divisi Teknologi Informasi (DTI)",
                "branchId": "100",
                "branchName": "Kantor Pusat",
                "role": "admin_it",
                "status": "AKTIF",
            },
        }

    return {"rcode": "01", "message": "Username atau Password HRIS tidak sesuai", "result": {}}
