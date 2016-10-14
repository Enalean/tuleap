import angular              from 'angular';
import ngSanitize           from 'angular-sanitize';
import 'angular-gettext';
import 'angular-moment';

import highlight_filter from '../highlight-filter/highlight-filter.js';

import CardFieldsService          from './card-fields-service.js';
import tuleapSimpleFieldDirective from './tuleap-simple-field-directive.js';
import cardComputedFieldDirective from './card-computed-field/card-computed-field-directive.js';
import cardTextFieldDirective     from './card-text-field/card-text-field-directive.js';

angular.module('card-fields', [
    ngSanitize,
    'gettext',
    'angularMoment',
    highlight_filter
])
.service('CardFieldsService', CardFieldsService)
.directive('tuleapSimpleField', tuleapSimpleFieldDirective)
.directive('cardComputedField', cardComputedFieldDirective)
.directive('cardTextField', cardTextFieldDirective);

export default 'card-fields';
