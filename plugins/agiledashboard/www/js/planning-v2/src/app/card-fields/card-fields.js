import angular from 'angular';

import 'angular-gettext';
import 'angular-moment';

import highlight_filter from '../highlight-filter/highlight-filter.js';

import CardComputedFieldDirective from './card-computed-field/card-computed-field-directive.js';
import CardFieldsService          from './card-fields-service.js';

export default angular.module('card-fields', [
    'gettext',
    'angularMoment',
    highlight_filter,
])
.directive('cardComputedField', CardComputedFieldDirective)
.service('CardFieldsService', CardFieldsService)
.name;
