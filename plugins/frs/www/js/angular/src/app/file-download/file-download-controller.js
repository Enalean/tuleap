angular
    .module('tuleap.frs')
    .controller('FileDownloadController', FileDownloadController);

FileDownloadController.$inject = [
    '$modal',
    '$window',
    'lodash'
];

function FileDownloadController(
    $modal,
    $window,
    _
) {
    var self = this;

    _.extend(self, {
        init        : init,
        downloadFile: downloadFile,

        file_download_url: null
    });

    self.init();

    function init() {
        if (_.has(self, 'file.download_url')) {
            self.file_download_url = decodeURIComponent(self.file.download_url);
        }
    }

    function downloadFile() {
        if (! self.license_approval_mandatory) {
            openDownloadWindow();

            return;
        }

        openLicenseModal().result.then(openDownloadWindow);
    }

    function openDownloadWindow() {
        $window.open(self.file_download_url);
    }

    function openLicenseModal() {
        return $modal.open({
            backdrop   : 'static',
            keyboard   : true,
            templateUrl: 'file-download/license-modal/license-modal.tpl.html',
            controller : 'LicenseModalController as $ctrl',
            windowClass: 'license-modal'
        });
    }
}
