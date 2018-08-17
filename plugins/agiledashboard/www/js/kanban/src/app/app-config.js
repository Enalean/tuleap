export default KanbanConfig;

KanbanConfig.$inject = ["$compileProvider", "RestangularProvider"];

function KanbanConfig($compileProvider, RestangularProvider) {
    RestangularProvider.setFullResponse(true);
    RestangularProvider.setBaseUrl("/api/v1");
    RestangularProvider.setDefaultHeaders({
        "Content-Type": "application/json"
    });

    // To remove this setting, move all init() code
    // of directive controllers to $onInit
    $compileProvider.preAssignBindingsEnabled(true);
}
