import { createDropdown } from "@tuleap/tlp-dropdown";

export default AddToDashboardController;

AddToDashboardController.$inject = ["$element", "SharedPropertiesService"];

function AddToDashboardController($element, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $onInit: init,
        dashboard_dropdown: SharedPropertiesService.getDashboardDropdown(),
        report_id: SharedPropertiesService.getSelectedTrackerReportId(),

        showProjectDashboards,
        showDashboardButton,
    });

    function init() {
        const dashboard_dropdown = $element[0].querySelector("#dashboard-dropdown-button");
        if (dashboard_dropdown) {
            createDropdown(dashboard_dropdown);
        }
    }

    function showProjectDashboards() {
        return userIsAdmin() && self.dashboard_dropdown.project_dashboards.length > 0;
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

    function showUserDashboards() {
        return self.dashboard_dropdown.user_dashboards.length > 0;
    }

    function showDashboardButton() {
        return showDashboardDropdown && (showUserDashboards() || showProjectDashboards());
    }
}
