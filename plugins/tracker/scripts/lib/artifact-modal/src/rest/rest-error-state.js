let error_message = null;

const getErrorMessage = () => error_message;
const hasError = () => error_message !== null;
const setError = (error) => {
    error_message = error;
};
const resetError = () => {
    error_message = null;
};

export { getErrorMessage, hasError, resetError, setError };
