import angular   from 'angular';
import ui_router from 'angular-ui-router';

import 'angular-gettext';
import '../../po/fr.po';

import MainController from './app-main-controller.js';
import SharedPropertiesService from './shared-properties-service.js';

export default angular.module('cross-tracker', [
    'gettext',
    ui_router,
])
.controller('MainController', MainController)
.service('SharedPropertiesService', SharedPropertiesService)
.name;
