import angular  from 'angular';
import dragular from 'dragular';
import 'angular-gettext';
import 'tuleap-artifact-modal';

import drop                  from '../drop/drop.js';
import highlight_filter      from '../highlight-filter/highlight-filter.js';
import backlog_item_selected from '../backlog-item-selected/backlog-item-selected.js';
import backlog_item_details  from './backlog-item-details/backlog-item-details.js';
import backlog_item_rest     from '../backlog-item-rest/backlog-item-rest.js';

import BacklogItemDirective from './backlog-item-directive.js';

export default angular.module('backlog-item', [
    'gettext',
    'tuleap.artifact-modal',
    backlog_item_details,
    backlog_item_rest,
    backlog_item_selected,
    dragular,
    drop,
    highlight_filter,
])
.directive('backlogItem', BacklogItemDirective)
.name;
