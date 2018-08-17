import _ from "lodash";

import { getDefinitions as tlpGetDefinitions } from "../api/rest-querier.js";

export default DefinitionService;

DefinitionService.$inject = ["Restangular", "$q", "DefinitionConstants", "SharedPropertiesService"];

function DefinitionService(Restangular, $q, DefinitionConstants, SharedPropertiesService) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        UNCATEGORIZED: DefinitionConstants.UNCATEGORIZED,
        getDefinitions,
        getDefinitionReports,
        getArtifactById,
        getDefinitionById,
        getTracker
    };

    function getDefinitions(project_id, report_id) {
        return tlpGetDefinitions(project_id, report_id).then(definitions =>
            categorize(definitions)
        );
    }

    function categorize(definitions) {
        return _.map(definitions, function(definition) {
            return _.merge(definition, {
                category: definition.category || DefinitionConstants.UNCATEGORIZED
            });
        });
    }

    function getDefinitionReports() {
        var data = $q.defer();
        var def_tracker_id = SharedPropertiesService.getDefinitionTrackerId();

        rest.one("trackers", def_tracker_id)
            .all("tracker_reports")
            .getList()
            .then(function(response) {
                data.resolve(response.data);
            });

        return data.promise;
    }

    function getArtifactById(artifact_id) {
        return rest
            .one("artifacts", artifact_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }

    function getDefinitionById(artifact_id) {
        return rest
            .one("testmanagement_definitions", artifact_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }

    function getTracker(tracker_id) {
        return rest
            .one("trackers", tracker_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }
}
