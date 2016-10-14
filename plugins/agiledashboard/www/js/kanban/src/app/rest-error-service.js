import _ from 'lodash';

export default RestErrorService;

RestErrorService.$inject = [
    '$modal'
];

function RestErrorService(
    $modal
) {
    var self = this;
    _.extend(self, {
        reload: reload
    });

    function reload(response) {
        $modal.open({
            keyboard    : false,
            backdrop    : 'static',
            templateUrl : 'error/error.tpl.html',
            controller  : 'ErrorCtrl',
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
