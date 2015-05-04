angular
    .module('kanban')
    .service('KanbanItemService', KanbanItemService);

KanbanItemService.$inject = ['Restangular', '$q'];

function KanbanItemService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        createItem         : createItem,
        createItemInBacklog: createItemInBacklog
    };

    function createItem(kanban_id, column_id, label) {
        return rest.one('kanban_items').post('', {
            label: label,
            kanban_id: kanban_id,
            column_id: column_id
        });
    }

    function createItemInBacklog(kanban_id, label) {
        return rest.one('kanban_items').post('', {
            label: label,
            kanban_id: kanban_id
        });
    }
}