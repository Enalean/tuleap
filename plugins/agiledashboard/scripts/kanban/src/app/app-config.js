export default KanbanConfig;

KanbanConfig.$inject = ["RestangularProvider"];

function KanbanConfig(RestangularProvider) {
    RestangularProvider.setFullResponse(true);
    RestangularProvider.setBaseUrl("/api/v1");
    RestangularProvider.setDefaultHeaders({
        "Content-Type": "application/json",
    });
}
