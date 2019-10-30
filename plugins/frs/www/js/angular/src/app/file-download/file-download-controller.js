/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import "./license-modal/license-modal.tpl.html";
import "./custom-license-modal/custom-license-modal.tpl.html";

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
        if (
            Object.prototype.hasOwnProperty.call(self, "file") &&
            Object.prototype.hasOwnProperty.call(self.file, "download_url")
        ) {
            self.file_download_url = decodeURIComponent(self.file.download_url);
        }
    }

    function downloadFile() {
        if (!self.license_approval_mandatory) {
            openDownloadWindow();

            return;
        }

        let licenseModal = openLicenseModal;
        if (
            self.custom_license_agreement &&
            Object.prototype.hasOwnProperty.call(self.custom_license_agreement, "title")
        ) {
            licenseModal = openCustomLicenseModal;
        }

        licenseModal().result.then(openDownloadWindow);
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

    function openCustomLicenseModal() {
        return $modal.open({
            backdrop: "static",
            keyboard: true,
            templateUrl: "custom-license-modal.tpl.html",
            controller: "CustomLicenseModalController as $ctrl",
            windowClass: "custom-license-modal"
        });
    }
}
