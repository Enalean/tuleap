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
import LicenseModalController from "./license-modal/license-modal-controller";
import CustomLicenseModalController from "./custom-license-modal/custom-license-modal-controller";

export default FileDownloadController;

FileDownloadController.$inject = ["TlpModalService", "$window"];

function FileDownloadController(TlpModalService, $window) {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        downloadFile,

        file_download_url: null,
    });

    function init() {
        if ("file" in self && "download_url" in self.file) {
            self.file_download_url = decodeURIComponent(self.file.download_url);
        }
    }

    function downloadFile() {
        if (!self.license_approval_mandatory) {
            openDownloadWindow();

            return;
        }

        if (
            self.custom_license_agreement &&
            Object.prototype.hasOwnProperty.call(self.custom_license_agreement, "title")
        ) {
            openCustomLicenseModal(openDownloadWindow);
            return;
        }
        openLicenseModal(openDownloadWindow);
    }

    function openDownloadWindow() {
        $window.open(self.file_download_url);
    }

    function openLicenseModal(acceptCallback) {
        return TlpModalService.open({
            templateUrl: "license-modal.tpl.html",
            controller: LicenseModalController,
            controllerAs: "$ctrl",
            tlpModalOptions: { destroy_on_hide: true },
            resolve: { acceptCallback },
        });
    }

    function openCustomLicenseModal(acceptCallback) {
        return TlpModalService.open({
            templateUrl: "custom-license-modal.tpl.html",
            controller: CustomLicenseModalController,
            controllerAs: "$ctrl",
            tlpModalOptions: { destroy_on_hide: true },
            resolve: { acceptCallback },
        });
    }
}
