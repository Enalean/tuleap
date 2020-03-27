import "./add-to-dashboard.tpl.html";
import AddToDashboardCtrl from "./add-to-dashboard-controller.js";

export default AddToDashboard;

function AddToDashboard() {
    return {
        restrict: "E",
        controller: AddToDashboardCtrl,
        controllerAs: "add_to_dashboard",
        templateUrl: "add-to-dashboard.tpl.html",
        scope: {},
    };
}
