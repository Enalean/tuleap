Planning.trackerBaseUrl = codendi.tracker.base_url;

document.observe('dom:loaded', function () {
    Planning.loadDraggables(document.body);
    Planning.loadDroppables(document.body);
});