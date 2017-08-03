import angular from 'angular';
import 'angular-gettext';

import card_fields from '../card-fields/card-fields.js';

import KanbanItemDirective from './kanban-item-directive.js';

angular.module('kanban-item', [
    'gettext',
    card_fields
])
.directive('kanbanItem', KanbanItemDirective);

export default 'kanban-item';
