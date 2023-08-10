/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import ui_router from "@uirouter/angularjs";
import ng_sanitize from "angular-sanitize";

import "angular-gettext";
import translations from "../../po/fr_FR.po";
import { setCatalog } from "./gettext-catalog";

import angular_tlp from "@tuleap/angular-tlp";

import MainController from "./app-controller.js";

import FileDiffComponent from "./file-diff/file-diff-component.js";
import UnidiffComponent from "./file-diff/diff-modes/unidiff-component.js";
import SideBySideDiffComponent from "./file-diff/diff-modes/side-by-side-diff-component.js";

import DashboardDirective from "./dashboard/dashboard-directive.js";
import PullRequestSummaryDirective from "./dashboard/pull-request-summary/pull-request-summary-directive.js";
import FilesDirective from "./files/files-directive.js";
import CommitsDirective from "./commits/commits-directive.js";
import PullRequestDirective from "./pull-request/pull-request-directive.js";
import PullRequestHeaderDirective from "./pull-request/header/header-directive.js";
import PullRequestRefsDirective from "./pull-request-refs/pull-request-refs.directive.js";

import UserRestService from "./user-rest-service.js";
import TooltipService from "./tooltip-service.js";
import ErrorModalService from "./error-modal/error-modal-service.js";
import PullRequestCollectionRestService from "./dashboard/pull-request-collection-rest-service.js";
import PullRequestCollectionService from "./dashboard/pull-request-collection-service.js";
import FileDiffRestService from "./file-diff/file-diff-rest-service.js";
import FilepathsService from "./files/filepaths-service.js";
import FilesRestService from "./files/files-rest-service.js";
import CommitsRestService from "./commits/commits-rest-service.js";
import PullRequestRestService from "./pull-request/pull-request-rest-service.js";
import PullRequestService from "./pull-request/pull-request-service.js";

import MainConfig from "./app-config.js";
import TuleapResize from "./resize/resize.js";
import SharedProperties from "./shared-properties/shared-properties.js";
import DashboardConfig from "./dashboard/dashboard-config.js";
import FileDiffConfig from "./file-diff/file-diff-config.js";
import FilesConfig from "./files/files-config.js";
import CommitsConfig from "./commits/commits-config.js";
import PullRequestConfig from "./pull-request/pull-request-config.js";

import angular_custom_elements_module from "angular-custom-elements";
import "./file-diff/widgets/placeholders/FileDiffPlaceholder.ts";
import "@tuleap/plugin-pullrequest-comments";

export default angular
    .module("tuleap.pull-request", [
        "gettext",
        angular_tlp,
        angular_custom_elements_module,
        ui_router,
        ng_sanitize,
        SharedProperties,
        TuleapResize,
    ])
    .controller("MainController", MainController)

    .component("fileDiff", FileDiffComponent)
    .component("fileUnidiff", UnidiffComponent)
    .component("sideBySideDiff", SideBySideDiffComponent)

    .directive("dashboard", DashboardDirective)
    .directive("pullRequestSummary", PullRequestSummaryDirective)
    .directive("files", FilesDirective)
    .directive("commits", CommitsDirective)
    .directive("pullRequest", PullRequestDirective)
    .directive("pullRequestHeader", PullRequestHeaderDirective)
    .directive("pullRequestRefs", PullRequestRefsDirective)

    .service("UserRestService", UserRestService)
    .service("ErrorModalService", ErrorModalService)
    .service("TooltipService", TooltipService)
    .service("PullRequestCollectionRestService", PullRequestCollectionRestService)
    .service("PullRequestCollectionService", PullRequestCollectionService)
    .service("FileDiffRestService", FileDiffRestService)
    .service("FilepathsService", FilepathsService)
    .service("FilesRestService", FilesRestService)
    .service("CommitsRestService", CommitsRestService)
    .service("PullRequestRestService", PullRequestRestService)
    .service("PullRequestService", PullRequestService)

    .config(MainConfig)
    .config(DashboardConfig)
    .config(FileDiffConfig)
    .config(FilesConfig)
    .config(CommitsConfig)
    .config(PullRequestConfig)
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
                setCatalog(gettextCatalog);
            }
        },
    ]).name;
