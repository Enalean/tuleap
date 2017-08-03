import { dropdown } from 'tlp';

export default AddToDashboardController;

AddToDashboardController.$inject = [
    '$element',
    'SharedPropertiesService',
];

function AddToDashboardController(
    $element,
    SharedPropertiesService
) {
    var self = this;

    self.dashboard_dropdown = SharedPropertiesService.getDashboardDropdown();

    self.showProjectDashboards = showProjectDashboards;
    self.showDashboardDropdown = showDashboardDropdown;

    function init() {
        var dashboard_dropdown = $element[0].querySelector('#dashboard-dropdown-button');
        if (dashboard_dropdown) {
            dropdown(dashboard_dropdown);
        }
    }

    init();

    function showProjectDashboards() {
        return userIsAdmin();
    }

    function showDashboardDropdown() {
        return ! userIsOnWdiget();
    }

    function userIsOnWdiget() {
        return SharedPropertiesService.getUserIsOnWidget();
    }

    function userIsAdmin() {
        return SharedPropertiesService.getUserIsAdmin();
    }
}
