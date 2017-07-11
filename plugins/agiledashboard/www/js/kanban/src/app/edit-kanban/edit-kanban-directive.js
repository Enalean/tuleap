import './edit-kanban.tpl.html';
import EditKanbanCtrl from './edit-kanban-controller.js';

export default EditKanban;

function EditKanban() {
    return {
        restrict: 'AE',
        scope   : {
            modal_instance: '=modalInstance',
            rebuild_scrollbars: '&rebuildScrollbars'
        },
        templateUrl     : 'edit-kanban.tpl.html',
        controller      : EditKanbanCtrl,
        controllerAs    : 'edit_modal',
        bindToController: true
    };
}
