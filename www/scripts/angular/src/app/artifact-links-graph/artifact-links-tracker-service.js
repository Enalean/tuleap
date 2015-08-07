(function () {
    angular
        .module('tuleap.artifact-links-graph')
        .service('ArtifactLinksTrackerService', ArtifactLinksTrackerService)
        .value('ArtifactLinksTrackersList', {
            trackers: {}
        });

    ArtifactLinksTrackerService.$inject = [
        'ArtifactLinksGraphRestService',
        'ArtifactLinksModelService',
        'ArtifactLinksTrackersList',
        '$q'
    ];

    function ArtifactLinksTrackerService(
        ArtifactLinksGraphRestService,
        ArtifactLinksModelService,
        ArtifactLinksTrackersList,
        $q
    ) {
        var self = this;

        _.extend(self, {
            initializeTrackers: initializeTrackers,
            getTrackersIds    : getTrackersIds
        });

        function initializeTrackers(artifacts) {

            var tracker_ids = getTrackersIds(artifacts);

            var promises = [];
            _(tracker_ids).forEach(function(tracker_id) {
                if (! trackerExist(tracker_id)) {
                    promises.push(ArtifactLinksGraphRestService.getTracker(tracker_id));
                }
            });
            return $q.all(promises).then(function(results) {
                _(results).compact().forEach(function(tracker) {
                    ArtifactLinksTrackersList.trackers[tracker.id] = tracker;
                });
                return ArtifactLinksTrackersList.trackers;
            });
        }

        function trackerExist(trackerToCheck_id) {
            return _.has(ArtifactLinksTrackersList.trackers, trackerToCheck_id);
        }

        function getTrackersIds(artifacts) {
            var tracker_ids = [];
            _(artifacts.nodes).compact().forEach(function (artifact) {
                var artifact_link_field = ArtifactLinksModelService.getArtifactLinksField(artifact);
                if (artifact_link_field) {
                    var trackers_ids_artifact = getTrackersIdsByLinks(artifact_link_field);
                    tracker_ids = tracker_ids.concat(trackers_ids_artifact);
                }
            });
            tracker_ids.push(artifacts.current_node.tracker.id);
            tracker_ids = _.uniq(tracker_ids);

            return tracker_ids;
        }

        function getTrackersIdsByLinks(artifact) {
            var tracker_ids = [];

            _(artifact.links).compact().forEach(function (link) {
                tracker_ids.push(link.tracker.id);
            });
            _(artifact.reverse_links).compact().forEach(function (reverse_link) {
                tracker_ids.push(reverse_link.tracker.id);
            });

            return tracker_ids;
        }
    }
})();