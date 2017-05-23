import './error.tpl.html';
import ErrorCtrl from './error-controller.js';

export default RestErrorService;

RestErrorService.$inject = [
    '$modal'
];

function RestErrorService(
    $modal
) {
    var self = this;
    self.reload = reload;

    function reload(response) {
        $modal.open({
            keyboard    : false,
            backdrop    : 'static',
            templateUrl : 'error.tpl.html',
            controller  : ErrorCtrl,
            controllerAs: 'modal',
            resolve     : {
                message: function() {
                    var message = response.status + ' ' + response.statusText;
                    if (response.data.error) {
                        message = response.data.error.code + ' ' + response.data.error.message;
                    }

                    return message;
                }
            }
        });
    }
}
