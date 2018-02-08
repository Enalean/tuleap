import angular              from 'angular';
import ngSanitize           from 'angular-sanitize';
import 'angular-gettext';
import 'angular-moment';

import highlight_filter from './highlight-filter.js';
import StripTagsFilter  from './strip-tags-filter.js';

import CardFieldsDirective        from './card-fields-directive.js';
import CardFieldsService          from './card-fields-service.js';
import tuleapSimpleFieldDirective from './tuleap-simple-field-directive.js';
import cardComputedFieldDirective from './card-computed-field/card-computed-field-directive.js';
import cardTextFieldDirective     from './card-text-field/card-text-field-directive.js';

export default angular.module('card-fields', [
    ngSanitize,
    'gettext',
    'angularMoment',
    highlight_filter,
])
.service('CardFieldsService', CardFieldsService)
.directive('cardFields', CardFieldsDirective)
.directive('tuleapSimpleField', tuleapSimpleFieldDirective)
.directive('cardComputedField', cardComputedFieldDirective)
.directive('cardTextField', cardTextFieldDirective)
.filter('tuleapStripTags', StripTagsFilter)
.name;
