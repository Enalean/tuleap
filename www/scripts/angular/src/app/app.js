import angular from 'angular';
import ngAnimate from 'angular-animate';
import ngSanitize from 'angular-sanitize';
import ui_router from 'angular-ui-router';
import animate_change from 'angular-animate-change';

import 'angular-breadcrumb';
import 'angular-gettext';
import '../../po/fr.po';
import 'angular-moment';

// Modal deps should be required by modal
import 'angular-ckeditor';
import 'angular-bootstrap-datetimepicker';
import 'angular-ui-select';
import 'angular-filter';
import 'angular-base64-upload';
import './modal-moment-fix.js';

import shared_properties from './shared-properties/shared-properties.js';
import uuid_generator from './uuid-generator/uuid-generator.js';
import socket from './socket/socket.js';
import jwt from './jwt/jwt.js';
import campaign from './campaign/campaign.js';
import execution from './execution/execution.js';
import definition from './definition/definition.js';
import graph from './graph/graph.js';
import artifact_links_graph from './artifact-links-graph/artifact-links-graph.js';

import TrafficlightsConfig from './app-config.js';
import AutoFocusDirective from './app-directive.js';
import InPropertiesFilter from './app-filter.js';
import TrafficlightsCtrl from './app-controller.js';

export default angular.module('trafficlights', [
    ngAnimate,
    ngSanitize,
    ui_router,
    animate_change,
    'ncy-angular-breadcrumb',
    'gettext',
    'angularMoment',
    shared_properties,
    uuid_generator,
    socket,
    jwt,
    campaign,
    execution,
    definition,
    graph,
    artifact_links_graph
])
.config(TrafficlightsConfig)
.directive('autoFocus', AutoFocusDirective)
.filter('InPropertiesFilter', InPropertiesFilter)
.controller('TrafficlightsCtrl', TrafficlightsCtrl)
.name;

