import angular from 'angular';

import 'restangular';

import ExecutionConfig from './execution-config.js';
import ExecutionRestService from './execution-rest-service.js';
import ExecutionService from './execution-service.js';
import ExecutionPresencesCtrl from './execution-presences-controller.js';
import ExecutionListCtrl from './execution-list-controller.js';
import ExecutionDetailCtrl from './execution-detail-controller.js';
import ExecutionLinkIssueCtrl from './execution-link-issue-controller.js';
import ExecutionTimerDirective from './timer/execution-timer-directive.js';
import ExecutionListFilter from './execution-list-filter.js';

export default angular.module('execution', [
    'restangular'
])
.config(ExecutionConfig)
.service('ExecutionRestService', ExecutionRestService)
.service('ExecutionService', ExecutionService)
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

