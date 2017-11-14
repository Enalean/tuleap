export default FilterTrackerReportController;

FilterTrackerReportController.$inject = [
    'SharedPropertiesService',
];

function FilterTrackerReportController(
    SharedPropertiesService
) {
    const self = this;

    self.selected_item      = null;
    self.filters_collection = Object.values(SharedPropertiesService.getFilters());

    self.$onInit = init;

    function init() {
        const item = self.filters_collection.find((filter) => { return filter.selected; });

        if (item) {
            self.selected_item = item.id;
        }
    }
}
