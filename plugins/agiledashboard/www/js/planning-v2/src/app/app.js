import angular    from 'angular';
import ngAnimate  from 'angular-animate';
import ngSanitize from 'angular-sanitize';
import ui_router  from 'angular-ui-router';

import 'angular-moment';
import 'moment/locale/fr.js';
import 'angular-gettext';
import 'restangular';
import 'angular-ui-bootstrap-templates';
import '../../po/fr.po';

import backlog               from './backlog/backlog.js';
import backlog_item_rest     from './backlog-item-rest/backlog-item-rest.js';
import backlog_item_selected from './backlog-item-selected/backlog-item-selected.js';
import edit_item             from './edit-item/edit-item.js';
import highlight_filter      from './highlight-filter/highlight-filter.js';
import in_properties         from './in-properties/in-properties.js';
import milestone             from './milestone/milestone.js';
import shared_properties     from './shared-properties/shared-properties.js';
import user_preferences      from './user-preferences/user-preferences.js';
import rest_error            from './rest-error/rest-error.js';

// Modal deps should be required by modal
import 'angular-ckeditor';
import 'angular-bootstrap-datetimepicker';
import 'angular-ui-select';
import 'angular-filter';
import 'angular-base64-upload';
import 'tuleap-artifact-modal';
import './modal-moment-fix.js';

import MainController     from './main-controller.js';
import PlanningConfig     from './app-config.js';
import PlanningController from './app-planning-controller.js';

export default angular.module('planning', [
    'angularMoment',
    'gettext',
    'tuleap.artifact-modal',
    ngSanitize,
    ngAnimate,
    ui_router,
    backlog,
    backlog_item_rest,
    backlog_item_selected,
    edit_item,
    highlight_filter,
    in_properties,
    milestone,
    rest_error,
    shared_properties,
    user_preferences,
])
.config(PlanningConfig)
.controller('MainController', MainController)
.controller('PlanningController', PlanningController)
.name;
