import angular from 'angular';
import filter from 'angular-filter';

import file_field from '../tuleap-artifact-modal-fields/file-field/file-field.js';

import QuotaDisplayDirective from './quota-display-directive.js';

angular.module('tuleap-artifact-modal-quota-display', [
    filter,
    file_field
])
.directive('tuleapArtifactModalQuotaDisplay', QuotaDisplayDirective);

export default 'tuleap-artifact-modal-quota-display';
