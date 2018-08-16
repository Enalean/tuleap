import "./license-modal/license-modal.tpl.html";

export default FileDownloadController;

FileDownloadController.$inject = ["$modal", "$window"];

function FileDownloadController($modal, $window) {
    const self = this;

    Object.assign(self, {
        init,
        downloadFile,

        file_download_url: null
    });

    self.init();

    function init() {
        if (self.hasOwnProperty("file") && self.file.hasOwnProperty("download_url")) {
            self.file_download_url = decodeURIComponent(self.file.download_url);
        }
    }

    function downloadFile() {
        if (!self.license_approval_mandatory) {
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
            backdrop: "static",
            keyboard: true,
            templateUrl: "license-modal.tpl.html",
            controller: "LicenseModalController as $ctrl",
            windowClass: "license-modal"
        });
    }
}
