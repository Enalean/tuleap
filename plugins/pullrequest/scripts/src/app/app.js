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
import ui_router from "angular-ui-router";
import ng_sanitize from "angular-sanitize";
import angular_moment from "angular-moment";

import "angular-gettext";
import translations from "../../po/fr.po";

import angular_tlp from "angular-tlp";

import MainController from "./app-controller.js";

import NewInlineCommentComponent from "./file-diff/new-inline-comment-component.js";
import InlineCommentComponent from "./file-diff/inline-comment-component.js";
import FileDiffComponent from "./file-diff/file-diff-component.js";
import UnidiffComponent from "./file-diff/diff-modes/unidiff-component.js";
import SideBySideDiffComponent from "./file-diff/diff-modes/side-by-side-diff-component.js";

import DashboardDirective from "./dashboard/dashboard-directive.js";
import PullRequestSummaryDirective from "./dashboard/pull-request-summary/pull-request-summary-directive.js";
import FilesDirective from "./files/files-directive.js";
import LabelsBox from "./labels/labels-directive.js";
import OverviewDirective from "./overview/overview-directive.js";
import ReviewersDirective from "./overview/reviewers/reviewers-directive.js";
import TimelineDirective from "./overview/timeline/timeline-directive.js";
import CommitsDirective from "./commits/commits-directive.js";
import PullRequestDirective from "./pull-request/pull-request-directive.js";
import PullRequestHeaderDirective from "./pull-request/header/header-directive.js";
import PullRequestRefsDirective from "./pull-request-refs/pull-request-refs.directive.js";
import TuleapUsernameDirective from "./tuleap-username/tuleap-username-directive.js";
import AutofocusInputDirective from "./autofocus-input-directive.js";

import UserRestService from "./user-rest-service.js";
import TooltipService from "./tooltip-service.js";
import ErrorModalService from "./error-modal/error-modal-service.js";
import PullRequestCollectionRestService from "./dashboard/pull-request-collection-rest-service.js";
import PullRequestCollectionService from "./dashboard/pull-request-collection-service.js";
import FileDiffRestService from "./file-diff/file-diff-rest-service.js";
import FilepathsService from "./files/filepaths-service.js";
import FilesRestService from "./files/files-rest-service.js";
import MergeModalService from "./overview/merge-modal/merge-modal-service.js";
import EditModalService from "./overview/edit-modal/edit-modal-service.js";
import ReviewersRestService from "./overview/reviewers/reviewers-rest-service.js";
import ReviewersService from "./overview/reviewers/reviewers-service.js";
import UpdateReviewersModalService from "./overview/reviewers/update-reviewers-modal/update-reviewers-modal-service.js";
import TimelineRestService from "./overview/timeline/timeline-rest-service.js";
import TimelineService from "./overview/timeline/timeline-service.js";
import CommitsRestService from "./commits/commits-rest-service.js";
import PullRequestRestService from "./pull-request/pull-request-rest-service.js";
import PullRequestService from "./pull-request/pull-request-service.js";
import CodeMirrorHelperService from "./file-diff/codemirror-helper-service.js";

import MainConfig from "./app-config.js";
import TuleapResize from "./resize/resize.js";
import SharedProperties from "./shared-properties/shared-properties.js";
import DashboardConfig from "./dashboard/dashboard-config.js";
import FileDiffConfig from "./file-diff/file-diff-config.js";
import FilesConfig from "./files/files-config.js";
import OverviewConfig from "./overview/overview-config.js";
import CommitsConfig from "./commits/commits-config.js";
import PullRequestConfig from "./pull-request/pull-request-config.js";

export default angular
    .module("tuleap.pull-request", [
        angular_moment,
        "gettext",
        angular_tlp,
        ui_router,
        ng_sanitize,
        SharedProperties,
        TuleapResize,
    ])
    .controller("MainController", MainController)

    .component("newInlineComment", NewInlineCommentComponent)
    .component("inlineComment", InlineCommentComponent)
    .component("fileDiff", FileDiffComponent)
    .component("fileUnidiff", UnidiffComponent)
    .component("sideBySideDiff", SideBySideDiffComponent)

    .directive("dashboard", DashboardDirective)
    .directive("pullRequestSummary", PullRequestSummaryDirective)
    .directive("files", FilesDirective)
    .directive("labelsBox", LabelsBox)
    .directive("overview", OverviewDirective)
    .directive("reviewers", ReviewersDirective)
    .directive("timeline", TimelineDirective)
    .directive("commits", CommitsDirective)
    .directive("pullRequest", PullRequestDirective)
    .directive("pullRequestHeader", PullRequestHeaderDirective)
    .directive("pullRequestRefs", PullRequestRefsDirective)
    .directive("tuleapUsername", TuleapUsernameDirective)
    .directive("autofocusInput", AutofocusInputDirective)

    .service("UserRestService", UserRestService)
    .service("ErrorModalService", ErrorModalService)
    .service("TooltipService", TooltipService)
    .service("PullRequestCollectionRestService", PullRequestCollectionRestService)
    .service("PullRequestCollectionService", PullRequestCollectionService)
    .service("FileDiffRestService", FileDiffRestService)
    .service("FilepathsService", FilepathsService)
    .service("FilesRestService", FilesRestService)
    .service("MergeModalService", MergeModalService)
    .service("EditModalService", EditModalService)
    .service("ReviewersRestService", ReviewersRestService)
    .service("ReviewersService", ReviewersService)
    .service("UpdateReviewersModalService", UpdateReviewersModalService)
    .service("TimelineRestService", TimelineRestService)
    .service("TimelineService", TimelineService)
    .service("CommitsRestService", CommitsRestService)
    .service("PullRequestRestService", PullRequestRestService)
    .service("PullRequestService", PullRequestService)
    .service("CodeMirrorHelperService", CodeMirrorHelperService)

    .config(MainConfig)
    .config(DashboardConfig)
    .config(FileDiffConfig)
    .config(FilesConfig)
    .config(OverviewConfig)
    .config(CommitsConfig)
    .config(PullRequestConfig)
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(translations)) {
                gettextCatalog.setStrings(language, strings);
            }
        },
    ]).name;
