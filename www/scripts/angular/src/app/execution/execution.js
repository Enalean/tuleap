import angular from 'angular';

import ui_router              from 'angular-ui-router';
import angular_artifact_modal from 'angular-artifact-modal';
import angular_tlp            from 'angular-tlp';

import 'restangular';
import 'angular-gettext';

import shared_props_module   from '../shared-properties/shared-properties.js';
import definition_module     from '../definition/definition.js';
import artifact_links_module from '../artifact-links-graph/artifact-links-graph.js';

import ExecutionConfig         from './execution-config.js';
import ExecutionRestService    from './execution-rest-service.js';
import ExecutionService        from './execution-service.js';
import LinkedIssueService      from './linked-issue-service.js';
import ExecutionPresencesCtrl  from './execution-presences-controller.js';
import ExecutionListCtrl       from './execution-list-controller.js';
import ExecutionDetailCtrl     from './execution-detail-controller.js';
import ExecutionLinkIssueCtrl  from './execution-link-issue-controller.js';
import ExecutionTimerDirective from './timer/execution-timer-directive.js';
import ExecutionListFilter     from './execution-list-filter.js';

export default angular.module('execution', [
    'gettext',
    'restangular',
    angular_tlp,
    angular_artifact_modal,
    artifact_links_module,
    definition_module,
    shared_props_module,
    ui_router,
])
.config(ExecutionConfig)
.service('ExecutionRestService', ExecutionRestService)
.service('ExecutionService', ExecutionService)
.service('LinkedIssueService', LinkedIssueService)
.controller('ExecutionPresencesCtrl', ExecutionPresencesCtrl)
.controller('ExecutionListCtrl', ExecutionListCtrl)
.controller('ExecutionDetailCtrl', ExecutionDetailCtrl)
.controller('ExecutionLinkIssueCtrl', ExecutionLinkIssueCtrl)
.directive('timer', ExecutionTimerDirective)
.filter('ExecutionListFilter', ExecutionListFilter)
.constant("ExecutionConstants", {
    "UNCATEGORIZED": "Uncategorized"
})
.name;

