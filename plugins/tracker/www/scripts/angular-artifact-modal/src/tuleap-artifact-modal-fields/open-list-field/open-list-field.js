import angular from 'angular';
import 'angular-gettext';

import rest from '../../rest/rest.js';

import StaticOpenListFieldDirective from './static-open-list-field-directive.js';
import UgroupsOpenListFieldDirective from './ugroups-open-list-field-directive.js';
import UsersOpenListFieldDirective from './users-open-list-field-directive.js';
import OpenListFieldService from './open-list-field-validate-service.js';

export default angular.module('tuleap-artifact-modal-open-list-field', [
    'gettext',
    rest
])
.directive('tuleapArtifactModalStaticOpenListField', StaticOpenListFieldDirective)
.directive('tuleapArtifactModalUgroupsOpenListField', UgroupsOpenListFieldDirective)
.directive('tuleapArtifactModalUsersOpenListField', UsersOpenListFieldDirective)
.service('TuleapArtifactModalOpenListFieldValidateService', OpenListFieldService)
.name;
