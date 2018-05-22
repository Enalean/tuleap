import angular       from 'angular';
import base64_upload from 'angular-base64-upload';
import filter        from 'angular-filter';

import 'angular-gettext';

import FileFieldDirective   from './file-field-directive.js';
import FileUploadService    from './file-upload-service.js';

angular.module('tuleap-artifact-modal-file-field', [
    filter,
    'gettext',
    base64_upload
])
.directive('tuleapArtifactModalFileField', FileFieldDirective)
.service('TuleapArtifactModalFileUploadService', FileUploadService);

export default 'tuleap-artifact-modal-file-field';
