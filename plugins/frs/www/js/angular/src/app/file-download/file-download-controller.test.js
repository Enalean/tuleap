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

import angular from "angular";
import tuleap_frs_module from "../app.js";
import file_download_controller from "./file-download-controller.js";

import "angular-mocks";

describe("FileDownloadController -", function() {
    var $controller, $modal, $q, $rootScope, $window, FileDownloadController;

    beforeEach(function() {
        angular.mock.module(tuleap_frs_module);

        angular.mock.inject(function(_$controller_, _$modal_, _$q_, _$rootScope_, _$window_) {
            $controller = _$controller_;
            $modal = _$modal_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            $window = _$window_;
        });
    });

    describe("init() -", function() {
        it("Given a file with an encoded download_url property had been bound to the controller, when I init the controller then there will be a file_download_url on the scope with the decoded download url", function() {
            var file = {
                name: "alphabetist.tar.gz",
                download_url:
                    "%2Fsenso%2Finflationism%3Fa%3Dsextillionth%26b%3Dunfishable%23tricostate"
            };

            FileDownloadController = $controller(
                file_download_controller,
                {},
                {
                    file: file
                }
            );

            var decoded_file_download = decodeURIComponent(
                FileDownloadController.file.download_url
            );
            expect(FileDownloadController.file_download_url).toEqual(decoded_file_download);
        });
    });

    describe("downloadFile() -", function() {
        beforeEach(function() {
            jest.spyOn($modal, "open").mockReturnValue({
                result: $q.when()
            });
            jest.spyOn($window, "open").mockImplementation(() => {});

            FileDownloadController = $controller(file_download_controller, {
                $modal: $modal,
                $window: $window
            });
        });

        it("Given a file had been bound to the controller and license approval was not mandatory, when I download the file then a new window will be opened ", function() {
            var file_download_url = "axilemma/ventrine?a=geoteuthis&b=autoxidizer#dithyramb";
            FileDownloadController.file_download_url = file_download_url;
            FileDownloadController.license_approval_mandatory = false;

            FileDownloadController.downloadFile();

            expect($modal.open).not.toHaveBeenCalled();
            expect($window.open).toHaveBeenCalledWith(file_download_url);
        });

        it("Given a file had been bound to the controller and license approval was mandatory, when I download the file, then the license modal will be opened and when it is accepted a new window will be opened with the computed file_download_url", function() {
            var file_download_url = "hsinfonie/mislayer?a=podatus&b=isocheim#psilosopher";
            FileDownloadController.file_download_url = file_download_url;
            FileDownloadController.license_approval_mandatory = true;
            FileDownloadController.custom_license_agreement = {};

            FileDownloadController.downloadFile();

            expect($modal.open).toHaveBeenCalledWith({
                backdrop: "static",
                keyboard: true,
                templateUrl: "license-modal.tpl.html",
                controller: "LicenseModalController as $ctrl",
                windowClass: "license-modal"
            });
            $rootScope.$apply();
            expect($window.open).toHaveBeenCalledWith(file_download_url);
        });

        it("Given a file had been bound to the controller and a custom license approval was mandatory, when I download the file, then the license modal will be opened with the custom text and when it is accepted a new window will be opened with the computed file_download_url", function() {
            var file_download_url = "hsinfonie/mislayer?a=podatus&b=isocheim#psilosopher";
            FileDownloadController.file_download_url = file_download_url;
            FileDownloadController.license_approval_mandatory = true;
            FileDownloadController.custom_license_agreement = {
                title: "A fine license agreement",
                content: "A fine text"
            };

            FileDownloadController.downloadFile();

            expect($modal.open).toHaveBeenCalledWith({
                backdrop: "static",
                keyboard: true,
                templateUrl: "custom-license-modal.tpl.html",
                controller: "CustomLicenseModalController as $ctrl",
                windowClass: "custom-license-modal"
            });
            $rootScope.$apply();
            expect($window.open).toHaveBeenCalledWith(file_download_url);
        });
    });
});
