import angular from 'angular';

import RestErrorService from './rest-error-service.js';

angular.module('rest-error', [
    'ui.bootstrap'
])
.service('RestErrorService', RestErrorService);

export default 'rest-error';
