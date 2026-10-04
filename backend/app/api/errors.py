class ApiError(Exception):
    def __init__(self, status_code: int, rcode: str, message: str) -> None:
        self.status_code = status_code
        self.rcode = rcode
        self.message = message
