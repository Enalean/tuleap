import angular from 'angular';
import 'angular-gettext';

import LinkFieldDirective from './link-field-directive.js';
import rest_module        from '../../rest/rest.js';

export default angular.module('tuleap-artifact-modal-link-field', [
    'gettext',
    rest_module,
])
.directive('tuleapArtifactModalLinkField', LinkFieldDirective)
.name;
