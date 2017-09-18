angular
    .module('tuleap.pull-request')
    .service('ErrorModalService', ErrorModalService);

ErrorModalService.$inject = [
    '$modal',
    'lodash'
];

function ErrorModalService(
    $modal,
    _
) {
    var self = this;

    _.extend(self, {
        showError: showError
    });

    function showError(response) {
        $modal.open({
            keyboard   : false,
            backdrop   : 'static',
            templateUrl: 'error-modal/error-modal.tpl.html',
            controller : 'ErrorModalController as error_modal',
            resolve    : {
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
