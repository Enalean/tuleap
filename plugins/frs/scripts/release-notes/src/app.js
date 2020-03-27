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
import ui_router from "angular-ui-router";
import ng_sanitize from "angular-sanitize";
import angular_tlp_module from "angular-tlp";

import "angular-gettext";
import "angular-filter";
import "ng-showdown";
import translations from "../po/fr.po";

import AppController from "./app-controller.js";
import RestErrorService from "./rest-error-service.js";
import SharedPropertiesService from "./shared-properties-service.js";
import FrsConfig from "./app-config.js";

import FileDownloadDirective from "./file-download/file-download-directive.js";

import LicenseModalController from "./file-download/license-modal/license-modal-controller.js";
import CustomLicenseModalController from "./file-download/custom-license-modal/custom-license-modal-controller";

import ReleaseDirective from "./release/release-directive.js";
import ReleaseRestService from "./release/release-rest-service.js";

import FilesConfig from "./release/files/files-config.js";
import FilesDirective from "./release/files/files-directive.js";

import LinkedArtifactsConfig from "./release/linked-artifacts/linked-artifacts-config.js";
import LinkedArtifactsDirective from "./release/linked-artifacts/linked-artifacts-directive.js";

export default angular
    .module("tuleap.frs", [
        "angular.filter",
        "gettext",
        "ng-showdown",
        angular_tlp_module,
        ng_sanitize,
        ui_router,
    ])
    .controller("AppController", AppController)
    .controller("LicenseModalController", LicenseModalController)
    .controller("CustomLicenseModalController", CustomLicenseModalController)

    .service("RestErrorService", RestErrorService)
    .service("SharedPropertiesService", SharedPropertiesService)
    .service("ReleaseRestService", ReleaseRestService)

    .directive("fileDownload", FileDownloadDirective)
    .directive("release", ReleaseDirective)
    .directive("files", FilesDirective)
    .directive("linkedArtifacts", LinkedArtifactsDirective)

    .config(FrsConfig)
    .config(FilesConfig)
    .config(LinkedArtifactsConfig)
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
            }
        },
    ]).name;
