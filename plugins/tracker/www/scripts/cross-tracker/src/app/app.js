import angular   from 'angular';

import 'angular-gettext';
import '../../po/fr.po';

import MainController from './app-main-controller.js';
import SharedPropertiesService from './shared-properties-service.js';
import EmptyStateDirective from './empty-state/empty-state-directive.js';

export default angular.module('cross-tracker', ['gettext'])
.controller('MainController', MainController)
.service('SharedPropertiesService', SharedPropertiesService)
.directive('emptyState', EmptyStateDirective)
.name;

var cross_tracker_elements = document.getElementsByClassName('widget-cross-tracker');
[].forEach.call(cross_tracker_elements, function (cross_tracker_element) {
    angular.bootstrap(cross_tracker_element, ['cross-tracker']);
});