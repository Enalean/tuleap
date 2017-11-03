import angular from 'angular';

import ui_router              from 'angular-ui-router';
import angular_artifact_modal from 'angular-artifact-modal';
import angular_tlp            from 'angular-tlp';

import 'restangular';
import 'angular-gettext';

import execution_collection_module from '../execution-collection/execution-collection.js';
import shared_props_module         from '../shared-properties/shared-properties.js';
import definition_module           from '../definition/definition.js';
import artifact_links_module       from '../artifact-links-graph/artifact-links-graph.js';
import socket_module               from '../socket/socket.js';

import ExecutionConfig         from './execution-config.js';
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
    execution_collection_module,
    shared_props_module,
    socket_module,
    ui_router,
])
.config(ExecutionConfig)
.controller('ExecutionPresencesCtrl', ExecutionPresencesCtrl)
.controller('ExecutionListCtrl', ExecutionListCtrl)
.controller('ExecutionDetailCtrl', ExecutionDetailCtrl)
.controller('ExecutionLinkIssueCtrl', ExecutionLinkIssueCtrl)
.directive('timer', ExecutionTimerDirective)
.filter('ExecutionListFilter', ExecutionListFilter)
.name;

