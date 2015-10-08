angular
    .module('kanban')
    .service('KanbanItemService', KanbanItemService);

KanbanItemService.$inject = ['Restangular', 'SharedPropertiesService'];

function KanbanItemService(Restangular, SharedPropertiesService) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
        RestangularConfigurer.setDefaultHeaders({"X-Client-UUID": SharedPropertiesService.getUUID()});
    });

    return {
        createItem         : createItem,
        createItemInBacklog: createItemInBacklog,
        getItem            : getItem
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

    function getItem(item_id) {
        return rest.one('kanban_items', item_id)
            .get().then(function(response) {
                return response.data;
            });
    }
}
