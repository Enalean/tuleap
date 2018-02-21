export { getSuccess, setSuccess };

let message;

function getSuccess() {
    return message;
}

function setSuccess(success_message) {
    message = success_message;
}
