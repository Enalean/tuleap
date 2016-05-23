angular
    .module('tuleap.frs')
    .directive('fileDownload', fileDownloadDirective);

function fileDownloadDirective() {
    return {
        restrict: 'A',
        scope   : {
            file                      : '=fileDownload',
            license_approval_mandatory: '=licenseApprovalMandatory'
        },
        template        : '<a href="" ng-click="$ctrl.downloadFile()">{{ $ctrl.file.name }}</a>',
        controller      : 'FileDownloadController as $ctrl',
        bindToController: true
    };
}
