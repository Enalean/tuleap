import angular from "angular";
import tuleap_frs_module from "tuleap-frs-module";
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
            spyOn($modal, "open").and.returnValue({
                result: $q.when()
            });
            spyOn($window, "open");

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

            FileDownloadController.downloadFile();

            expect($modal.open).toHaveBeenCalled();
            $rootScope.$apply();
            expect($window.open).toHaveBeenCalledWith(file_download_url);
        });
    });
});
