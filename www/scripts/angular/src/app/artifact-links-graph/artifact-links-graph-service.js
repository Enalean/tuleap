angular
    .module('tuleap.artifact-links-graph')
    .service('ArtifactLinksGraphService', ArtifactLinksGraphService)
    .value('ArtifactLinksGraphModalLoading', {
        loading: {
            is_loading: false
        }
    });

ArtifactLinksGraphService.$inject = [
    '$modal',
    'gettextCatalog',
    'ArtifactLinksGraphModalLoading',
    'ArtifactLinksGraphRestService',
    'SharedPropertiesService',
    '$q'
];

function ArtifactLinksGraphService(
    $modal,
    gettextCatalog,
    ArtifactLinksGraphModalLoading,
    ArtifactLinksGraphRestService,
    SharedPropertiesService,
    $q
) {
    var self = this;

    _.extend(self, {
        showGraph        : showGraph,
        getGraphStructure: getGraphStructure
    });

    function showGraph(execution, definition) {
        ArtifactLinksGraphModalLoading.loading.is_loading = true;

        return $modal.open({
            backdrop   : 'static',
            templateUrl: 'artifact-links-graph/artifact-links-graph.tpl.html',
            controller : 'ArtifactLinksGraphCtrl as modal',
            resolve: {
                modal_model: function () {
                    var promises = [];
                    promises.push(ArtifactLinksGraphRestService.getArtifact(execution.id));
                    promises.push(ArtifactLinksGraphRestService.getArtifact(definition.id));
                    return $q.all(promises).then(function(results) {
                        return initializeTrackers(results[0], results[1]).then(function(trackers) {
                            return self.getGraphStructure(results[0], results[1], trackers);
                        });
                    });
                },
                title: function() {
                    return definition.summary;
                }
            }
        });
    }

    function initializeTrackers(execution, definition) {
        var trackers = {};

        var execution_link_field = getArtifactLinksField(execution);
        var definition_link_field = getArtifactLinksField(definition);

        var tracker_execution_ids = getTrackersIdsByLinks(execution_link_field);
        var tracker_definition_ids = getTrackersIdsByLinks(definition_link_field);

        var tracker_ids = tracker_definition_ids.concat(tracker_execution_ids);
        tracker_ids.push(definition.tracker.id);
        tracker_ids = _.uniq(tracker_ids);

        var promises = [];
        _(tracker_ids).forEach(function(tracker_id) {
            promises.push(ArtifactLinksGraphRestService.getTracker(tracker_id));
        });
        return $q.all(promises).then(function(results) {
            _(results).forEach(function(tracker) {
               trackers[tracker.id] = tracker;
            });
            return trackers;
        });
    }

    function getTrackersIdsByLinks(artifact) {
        var tracker_ids = [];
        _(artifact.links).forEach(function (link) {
            tracker_ids.push(link.tracker.id);
        });
        _(artifact.reverse_links).forEach(function (reverse_link) {
            tracker_ids.push(reverse_link.tracker.id);
        });
        return tracker_ids;
    }

    function getGraphStructure(execution, definition, trackers) {
        var modal_model = {
            errors: [],
            graph : {
                links: [],
                nodes: []
            }
        };

        var execution_link_field = getArtifactLinksField(execution);
        var definition_link_field = getArtifactLinksField(definition);

        if (! execution_link_field || ! definition_link_field) {
            modal_model.errors.push(gettextCatalog.getString('Artifact links field not found.'));

        } else {
            var outgoing_execution_links = execution_link_field.links,
                incoming_execution_links = execution_link_field.reverse_links;

            var outgoing_definition_links = definition_link_field.links,
                incoming_definition_links = definition_link_field.reverse_links;

            createNodeForCurrentArtifact(modal_model.graph, definition, trackers);

            createNodesAndLinksForOutgoingLinks(modal_model.graph, definition, outgoing_execution_links, trackers);
            createNodesAndLinksForIncomingLinks(modal_model.graph, definition, incoming_execution_links, trackers);

            createNodesAndLinksForOutgoingLinks(modal_model.graph, definition, outgoing_definition_links, trackers);
            createNodesAndLinksForIncomingLinks(modal_model.graph, definition, incoming_definition_links, trackers);

            modal_model.graph.nodes = _.uniq(modal_model.graph.nodes, 'id');
        }
        return modal_model;
    }

    function getArtifactLinksField(artifact) {
        return _.find(artifact.values, { type: 'art_link' });
    }

    function createNodeForCurrentArtifact(graph, artifact, trackers) {
        var me = { id: artifact.id, label: trackers[artifact.tracker.id].item_name + ' #' + artifact.id, color_name: trackers[artifact.tracker.id].color_name };
        graph.nodes.push(me);
    }

    function createNodesAndLinksForOutgoingLinks(graph, artifact, outgoing_links, trackers) {
        _(outgoing_links).forEach(function (outgoing_link) {
            if (outgoing_link.id !== artifact.id &&
                outgoing_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {source: artifact.id, target: outgoing_link.id, type: 'arrow'},
                    node = {id: outgoing_link.id, label: trackers[outgoing_link.tracker.id].item_name + ' #' + outgoing_link.id, color_name: trackers[outgoing_link.tracker.id].color_name};

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }

    function createNodesAndLinksForIncomingLinks(graph, artifact, incoming_links, trackers) {
        _(incoming_links).forEach(function(incoming_link) {
            if (incoming_link.id !== artifact.id &&
                incoming_link.tracker.id !== SharedPropertiesService.getTrackerExecutionId()) {

                var link = {source: incoming_link.id, target: artifact.id, type: 'arrow'},
                    node = {id: incoming_link.id, label: trackers[incoming_link.tracker.id].item_name + ' #' + incoming_link.id, color_name: trackers[incoming_link.tracker.id].color_name};

                graph.links.push(link);
                graph.nodes.push(node);
            }
        });
    }
}
