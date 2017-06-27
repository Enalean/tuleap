import angular    from 'angular';
import ngAnimate  from 'angular-animate';
import ngSanitize from 'angular-sanitize';
import dragular   from 'dragular';
import ui_router  from 'angular-ui-router';

import 'angular-locker';
import 'angular-moment';
import 'angular-gettext';
import 'ng-scrollbar';
import 'restangular';
import 'angular-ui-bootstrap-templates';
import '../../po/fr.po';

import jwt               from './jwt/jwt.js';
import kanban_item       from './kanban-item/kanban-item.js';
import shared_properties from './shared-properties/shared-properties.js';
import uuid_generator    from './uuid-generator/uuid-generator.js';
import socket            from './socket/socket.js';
import user_preferences  from './user-preferences/user-preferences.js';

// Modal deps should be required by modal
import 'angular-ckeditor';
import 'angular-bootstrap-datetimepicker';
import 'angular-ui-select';
import 'angular-filter';
import 'angular-base64-upload';
import 'tuleap-artifact-modal';
import './modal-moment-fix.js';

import KanbanConfig            from './app-config.js';
import MainCtrl                from './app-main-controller.js';
import KanbanCtrl              from './app-kanban-controller.js';
import KanbanService           from './kanban-service.js';
import RestErrorService        from './rest-error-service.js';
import ColumnCollectionService from './column-collection-service.js';
import DroppedService          from './dropped-service.js';
import KanbanFilterValue       from './filter-value.js';
import AddInPlaceDirective     from './add-in-place/add-in-place-directive.js';
import AutoFocusInputDirective from './edit-kanban/edit-kanban-autofocus-directive.js';
import EscKeyDirective         from './esc-key/esc-key-directive.js';
import InPropertiesFilter      from './in-properties-filter/in-properties-filter.js';
import KanbanColumnDirective   from './kanban-column/kanban-column-directive.js';
import KanbanColumnService     from './kanban-column/kanban-column-service.js';
import KanbanItemRestService   from './kanban-item/kanban-item-rest-service.js';
import GraphDirective          from './reports-modal/diagram-directive.js';
import DiagramRestService      from './reports-modal/diagram-rest-service.js';
import ReportsModalController  from './reports-modal/reports-modal-controller.js';
import TuleapStripTagsFilter   from './strip-tags/strip-tags-filter.js';
import WipPopoverDirective     from './wip-popover/wip-popover-directive.js';
import EditKanbanCtrl          from './edit-kanban/edit-kanban-controller.js';
import ErrorCtrl               from './error/error-controller.js';
import KanbanColumnController  from './kanban-column/kanban-column-controller.js';

angular.module('kanban', [
    'angular-locker',
    'angularMoment',
    'gettext',
    'ngScrollbar',
    'restangular',
    'ui.bootstrap',
    'tuleap.artifact-modal',
    dragular,
    jwt,
    ngAnimate,
    ngSanitize,
    shared_properties,
    socket,
    ui_router,
    user_preferences,
    uuid_generator,
    kanban_item
])
.config(KanbanConfig)
.controller('MainCtrl', MainCtrl)
.controller('KanbanCtrl', KanbanCtrl)
.controller('ReportsModalController', ReportsModalController)
.controller('EditKanbanCtrl', EditKanbanCtrl)
.controller('ErrorCtrl', ErrorCtrl)
.controller('KanbanColumnController', KanbanColumnController)
.service('KanbanService', KanbanService)
.service('RestErrorService', RestErrorService)
.service('ColumnCollectionService', ColumnCollectionService)
.service('DroppedService', DroppedService)
.service('KanbanColumnService', KanbanColumnService)
.service('KanbanItemRestService', KanbanItemRestService)
.service('DiagramRestService', DiagramRestService)
.directive('addInPlace', AddInPlaceDirective)
.directive('autoFocusInput', AutoFocusInputDirective)
.directive('escKey', EscKeyDirective)
.directive('kanbanColumn', KanbanColumnDirective)
.directive('graph', GraphDirective)
.directive('wipPopover', WipPopoverDirective)
.value('KanbanFilterValue', KanbanFilterValue)
.filter('InPropertiesFilter', InPropertiesFilter)
.filter('tuleapStripTags', TuleapStripTagsFilter);

export default 'kanban';
