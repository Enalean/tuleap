import _ from "lodash";

export default KanbanItemRestService;

KanbanItemRestService.$inject = [
    "$q",
    "Restangular",
    "SharedPropertiesService",
    "RestErrorService",
];

function KanbanItemRestService($q, Restangular, SharedPropertiesService, RestErrorService) {
    _.extend(Restangular.configuration.defaultHeaders, {
        "X-Client-UUID": SharedPropertiesService.getUUID(),
    });

    return {
        createItem: createItem,
        createItemInBacklog: createItemInBacklog,
        getItem: getItem,
    };

    function createItem(kanban_id, column_id, label) {
        return Restangular.one("kanban_items").post("", {
            label: label,
            kanban_id: kanban_id,
            column_id: column_id,
        });
    }

    function createItemInBacklog(kanban_id, label) {
        return Restangular.one("kanban_items").post("", {
            label: label,
            kanban_id: kanban_id,
        });
    }

    function getItem(item_id) {
        return Restangular.one("kanban_items", item_id)
            .get()
            .then(function (response) {
                return response.data;
            })
            .catch(catchRestError);
    }

    function catchRestError(data) {
        RestErrorService.reload(data);

        return $q.reject();
    }
}
