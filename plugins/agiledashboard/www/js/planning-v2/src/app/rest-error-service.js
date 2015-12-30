angular
    .module('planning')
    .service('RestErrorService', RestErrorService);

RestErrorService.$inject = [

];

function RestErrorService(

) {
    var self = this;
    _.extend(self, {
        getError: getError,
        setError: setError
    });

    var error = {
        rest_error        : '',
        rest_error_occured: false
    };

    function getError() {
        return error;
    }

    function setError(rest_error) {
        error.rest_error_occured = true;
        error.rest_error         = rest_error.code + ' ' + rest_error.message;
    }
}
