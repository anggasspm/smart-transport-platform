from datetime import datetime, timezone

def success_response(data, message: str, status_code: int=200):
    return {
        "status": "success",
        "code": status_code,
        "data": data,
        "message": message,
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "service": "ml-service"
    }

def error_response(message: str, data: dict | None = None, status_code: int = 400):
    return {
        "status": "error",
        "code": status_code,
        "data": data,
        "message": message,
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "service": "ml-service"
    }