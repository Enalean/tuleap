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
            backlogItem.updating           = false;
            backlogItem.children           = {};
            backlogItem.children.data      = [];
            backlogItem.children.loaded    = false;
            backlogItem.children.collapsed = true;

            backlogItem.isOpen = function() {
                return backlogItem.status === 'Open';
            };

            defineAllowedBacklogItemTypes(backlogItem);
        }

        function defineAllowedBacklogItemTypes(backlogItem) {
            var tracker_id       = backlogItem.artifact.tracker.id;
            var allowed_trackers = backlogItem.accept.trackers;

            backlogItem.accepted_types = {
                content : allowed_trackers,
                toString: function() {
                    var accept = [];
                    _.forEach(this.content, function(allowed_tracker) {
                        accept.push('trackerId' + allowed_tracker.id);
                    });

                    return accept.join('|');
                }
            };

            backlogItem.trackerId = getTrackerType(tracker_id);
        }

        function getTrackerType(tracker_id) {
            var prefix = 'trackerId';
            return prefix.concat(tracker_id);
        }
    }
})();
