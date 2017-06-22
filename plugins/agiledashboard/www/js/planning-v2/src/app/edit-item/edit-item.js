import angular from 'angular';

import 'tuleap-artifact-modal';
import shared_properties from '../shared-properties/shared-properties.js';
import milestone_rest    from '../milestone-rest/milestone-rest.js';

import EditItemService from './edit-item-service.js';

export default angular.module('edit-item', [
    'tuleap.artifact-modal',
    milestone_rest,
    shared_properties
])
.service('EditItemService', EditItemService)
.name;
