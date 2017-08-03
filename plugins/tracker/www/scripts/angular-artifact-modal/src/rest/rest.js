import angular from 'angular';
import 'restangular';

import RestService from './rest-service.js';

angular.module('tuleap-artifact-modal-rest', [
    'restangular'
])
.service('TuleapArtifactModalRestService', RestService);

export default 'tuleap-artifact-modal-rest';
