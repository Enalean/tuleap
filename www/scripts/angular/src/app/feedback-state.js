let success_message, error_message;

export function getSuccess() {
    return success_message;
}

export function setSuccess(success) {
    success_message = success;
}

export function getError() {
    return error_message;
}

export function setError(error) {
    error_message = error;
}

export function resetSuccess() {
    success_message = null;
}

export function resetError() {
    error_message = null;
}
