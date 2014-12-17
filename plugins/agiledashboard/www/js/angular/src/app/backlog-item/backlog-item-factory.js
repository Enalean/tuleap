(function () {
    angular
        .module('backlog-item')
        .factory('BacklogItemFactory', BacklogItemFactory);

    BacklogItemFactory.$inject = [];

    function BacklogItemFactory() {
        return {
            augment : augment
        };

        function augment(backlogItem) {
            backlogItem.updating        = false;
            backlogItem.children        = {};
            backlogItem.children.data   = [];
            backlogItem.children.loaded = false;

            backlogItem.isOpen = function() {
                return backlogItem.status === 'Open';
            };

            defineAllowedBacklogItemTypes(backlogItem);
        }

        function defineAllowedBacklogItemTypes(backlogItem) {
            var tracker_id       = backlogItem.artifact.tracker.id;
            var allowed_trackers = backlogItem.accept.trackers;
            var accept           = [];

            _.forEach(allowed_trackers, function(allowed_tracker) {
                accept.push(getTrackerType(allowed_tracker.id));
            });

            backlogItem.accepted_types = accept.join('|');
            backlogItem.trackerId      = getTrackerType(tracker_id);
        }

        function getTrackerType(tracker_id) {
            var prefix = 'trackerId';
            return prefix.concat(tracker_id);
        }
    }
})();