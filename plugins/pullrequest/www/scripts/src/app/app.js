import angular from "angular";
import ui_router from "angular-ui-router";
import ng_sanitize from "angular-sanitize";

import "angular-moment";
import "angular-gettext";
import "../../po/fr.po";

import angular_tlp from "angular-tlp";

import MainController from "./app-controller.js";

import NewInlineCommentComponent from "./file-diff/new-inline-comment-component.js";

import DashboardDirective from "./dashboard/dashboard-directive.js";
import PullRequestSummaryDirective from "./dashboard/pull-request-summary/pull-request-summary-directive.js";
import FileDiffDirective from "./file-diff/file-diff-directive.js";
import InlineCommentDirective from "./file-diff/inline-comment/inline-comment-directive.js";
import FilesDirective from "./files/files-directive.js";
import LabelsBox from "./labels/labels-directive.js";
import OverviewDirective from "./overview/overview-directive.js";
import TimelineDirective from "./overview/timeline/timeline-directive.js";
import PullRequestDirective from "./pull-request/pull-request-directive.js";
import PullRequestHeaderDirective from "./pull-request/header/header-directive.js";
import PullRequestRefsDirective from "./pull-request-refs/pull-request-refs.directive.js";
import TuleapUsernameDirective from "./tuleap-username/tuleap-username-directive.js";

import UserRestService from "./user-rest-service.js";
import TooltipService from "./tooltip-service.js";
import ErrorModalService from "./error-modal/error-modal-service.js";
import PullRequestCollectionRestService from "./dashboard/pull-request-collection-rest-service.js";
import PullRequestCollectionService from "./dashboard/pull-request-collection-service.js";
import FileDiffRestService from "./file-diff/file-diff-rest-service.js";
import FilepathsService from "./files/filepaths-service.js";
import FilesRestService from "./files/files-rest-service.js";
import MergeModalService from "./overview/merge-modal/merge-modal-service.js";
import TimelineRestService from "./overview/timeline/timeline-rest-service.js";
import TimelineService from "./overview/timeline/timeline-service.js";
import PullRequestRestService from "./pull-request/pull-request-rest-service.js";
import PullRequestService from "./pull-request/pull-request-service.js";

import MainConfig from "./app-config.js";
import TuleapResize from "./resize/resize.js";
import SharedProperties from "./shared-properties/shared-properties.js";
import DashboardConfig from "./dashboard/dashboard-config.js";
import FileDiffConfig from "./file-diff/file-diff-config.js";
import FilesConfig from "./files/files-config.js";
import OverviewConfig from "./overview/overview-config.js";
import PullRequestConfig from "./pull-request/pull-request-config.js";

export default angular
    .module("tuleap.pull-request", [
        "angularMoment",
        "gettext",
        angular_tlp,
        ui_router,
        ng_sanitize,
        SharedProperties,
        TuleapResize
    ])
    .controller("MainController", MainController)

    .component("newInlineComment", NewInlineCommentComponent)

    .directive("dashboard", DashboardDirective)
    .directive("pullRequestSummary", PullRequestSummaryDirective)
    .directive("fileDiff", FileDiffDirective)
    .directive("inlineComment", InlineCommentDirective)
    .directive("files", FilesDirective)
    .directive("labelsBox", LabelsBox)
    .directive("overview", OverviewDirective)
    .directive("timeline", TimelineDirective)
    .directive("pullRequest", PullRequestDirective)
    .directive("pullRequestHeader", PullRequestHeaderDirective)
    .directive("pullRequestRefs", PullRequestRefsDirective)
    .directive("tuleapUsername", TuleapUsernameDirective)

    .service("UserRestService", UserRestService)
    .service("ErrorModalService", ErrorModalService)
    .service("TooltipService", TooltipService)
    .service("PullRequestCollectionRestService", PullRequestCollectionRestService)
    .service("PullRequestCollectionService", PullRequestCollectionService)
    .service("FileDiffRestService", FileDiffRestService)
    .service("FilepathsService", FilepathsService)
    .service("FilesRestService", FilesRestService)
    .service("MergeModalService", MergeModalService)
    .service("TimelineRestService", TimelineRestService)
    .service("TimelineService", TimelineService)
    .service("PullRequestRestService", PullRequestRestService)
    .service("PullRequestService", PullRequestService)

    .config(MainConfig)
    .config(DashboardConfig)
    .config(FileDiffConfig)
    .config(FilesConfig)
    .config(OverviewConfig)
    .config(PullRequestConfig).name;
