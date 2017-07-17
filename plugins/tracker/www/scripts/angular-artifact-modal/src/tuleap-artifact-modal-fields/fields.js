import angular from 'angular';

import computed_field   from './computed-field/computed-field.js';
import permission_field from './permission-field/permission-field.js';
import file_field       from './file-field/file-field.js';
import date_field       from './date-field/date-field.js';

angular.module('tuleap-artifact-modal-fields', [
    file_field,
    computed_field,
    permission_field,
    date_field
]);

export default 'tuleap-artifact-modal-fields';
