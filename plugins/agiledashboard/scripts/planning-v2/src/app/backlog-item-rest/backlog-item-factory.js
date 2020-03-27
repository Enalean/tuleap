export default BacklogItemFactory;

BacklogItemFactory.$inject = [];

function BacklogItemFactory() {
    return {
        augment: augment,
    };

    function augment(backlog_item) {
        backlog_item.updating = false;
        backlog_item.shaking = false;
        backlog_item.selected = false;
        backlog_item.hidden = false;
        backlog_item.multiple = false;
        backlog_item.moving_to = false;

        backlog_item.children = {};
        backlog_item.children.data = [];
        backlog_item.children.loaded = false;
        backlog_item.children.collapsed = true;

        backlog_item.isOpen = function () {
            return backlog_item.status === "Open";
        };

        defineAllowedBacklogItemTypes(backlog_item);
    }

    function defineAllowedBacklogItemTypes(backlog_item) {
        var tracker_id = backlog_item.artifact.tracker.id;
        var allowed_trackers = backlog_item.accept.trackers;

        backlog_item.accepted_types = {
            content: allowed_trackers,
            toString() {
                return this.content
                    .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                    .join("|");
            },
        };

        backlog_item.trackerId = getTrackerType(tracker_id);
    }

    function getTrackerType(tracker_id) {
        var prefix = "trackerId";
        return prefix.concat(tracker_id);
    }
}
