import { dropdown } from "tlp";

export default AddToDashboardController;

AddToDashboardController.$inject = ["$element", "SharedPropertiesService"];

function AddToDashboardController($element, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        dashboard_dropdown: SharedPropertiesService.getDashboardDropdown(),
        report_id: SharedPropertiesService.getSelectedTrackerReportId(),

        showProjectDashboards,
        showDashboardDropdown
    });

    function init() {
        const dashboard_dropdown = $element[0].querySelector("#dashboard-dropdown-button");
        if (dashboard_dropdown) {
            dropdown(dashboard_dropdown);
        }
    }

    function showProjectDashboards() {
        return userIsAdmin();
    }

    function showDashboardDropdown() {
        return !userIsOnWdiget();
    }

    function userIsOnWdiget() {
        return SharedPropertiesService.getUserIsOnWidget();
    }

    function userIsAdmin() {
        return SharedPropertiesService.getUserIsAdmin();
    }
}
