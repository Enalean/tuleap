import angular     from 'angular';
import ui_router   from 'angular-ui-router';
import ng_sanitize from 'angular-sanitize';

import 'angular-gettext';
import 'angular-filter';
import 'ng-showdown';
import 'angular-ui-bootstrap-templates';
import '../../po/fr.po';

import AppController           from './app-controller.js';
import RestErrorService        from './rest-error-service.js';
import SharedPropertiesService from './shared-properties-service.js';
import FrsConfig               from './app-config.js';

import FileDownloadDirective   from './file-download/file-download-directive.js';

import LicenseModalController  from './file-download/license-modal/license-modal-controller.js';

import ReleaseDirective        from './release/release-directive.js';
import ReleaseRestService      from './release/release-rest-service.js';

import FilesConfig             from './release/files/files-config.js';
import FilesDirective          from './release/files/files-directive.js';

import LinkedArtifactsConfig     from './release/linked-artifacts/linked-artifacts-config.js';
import LinkedArtifactsDirective  from './release/linked-artifacts/linked-artifacts-directive.js';

export default angular.module('tuleap.frs', [
    'angular.filter',
    'gettext',
    'ng-showdown',
    'ui.bootstrap',
    ng_sanitize,
    ui_router
])
    .controller('AppController', AppController)
    .controller('LicenseModalController', LicenseModalController)

    .service('RestErrorService', RestErrorService)
    .service('SharedPropertiesService', SharedPropertiesService)
    .service('ReleaseRestService', ReleaseRestService)

    .directive('fileDownload', FileDownloadDirective)
    .directive('release', ReleaseDirective)
    .directive('files', FilesDirective)
    .directive('linkedArtifacts', LinkedArtifactsDirective)

    .config(FrsConfig)
    .config(FilesConfig)
    .config(LinkedArtifactsConfig)

    .name;
