import angular from 'angular';

import 'tuleap-artifact-modal';

// tuleap.artifact-modal deps
import 'angular-moment';

import GraphConfig from './graph-config.js';
import GraphCtrl from './graph-controller.js';

export default angular.module('graph', [
    'tuleap.artifact-modal'
])
.config(GraphConfig)
.controller('GraphCtrl', GraphCtrl)
.name;

