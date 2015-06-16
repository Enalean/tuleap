angular
    .module('tuleap.artifact-modal')
    .service('TuleapArtifactModalRestService', TuleapArtifactModalRestService);

TuleapArtifactModalRestService.$inject = ['Restangular'];

function TuleapArtifactModalRestService(Restangular) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
        RestangularConfigurer.addResponseInterceptor(responseInterceptor);
        RestangularConfigurer.setErrorInterceptor(errorInterceptor);
    });

    var service = {
        createArtifact          : createArtifact,
        editArtifact            : editArtifact,
        getArtifact             : getArtifact,
        getArtifactsTitles      : getArtifactsTitles,
        getTrackerArtifacts     : getTrackerArtifacts,
        getTrackerStructure     : getTrackerStructure,

        error: {
            is_error     : false,
            error_message: null
        }
    };
    return service;

    function getTrackerStructure(tracker_id) {
        return rest.one('trackers', tracker_id)
            .withHttpConfig({
                cache: true
            }).get().then(function(response) {
                return response.data;
            });
    }

    function getTrackerArtifacts(tracker_id) {
        return rest.one('trackers', tracker_id).all('artifacts')
            .getList({
                values : "all"
            }).then(function(response) {
                return response.data;
            });
    }

    function getArtifact(artifact_id) {
        return rest.one('artifacts', artifact_id)
            .get().then(function(response) {
                return response.data;
            });
    }

    // Used to fill the <select> that lets you choose the parent artifact
    function getArtifactsTitles(tracker_id) {
        var parent_title_field_id, tracker_label;

        var promise = service.getTrackerStructure(tracker_id).then(function(structure) {
            parent_title_field_id = structure.semantics.title.field_id;
            tracker_label = structure.label;
            return service.getTrackerArtifacts(tracker_id);
        }).then (function(parent_artifacts) {
            var simplified_artifacts = _.map(parent_artifacts, function(artifact) {
                var field = _(artifact.values).find({ field_id: parent_title_field_id });
                var title_value = _.result(field, 'value');
                return {
                    id    : artifact.id,
                    title : tracker_label +' #'+ artifact.id +' - '+ title_value
                };
            });
            return simplified_artifacts;
        });
        return promise;
    }

    function createArtifact(tracker_id, field_values) {
        var promise = rest.service('artifacts').post({
            tracker : {
                id : tracker_id
            },
            values  : field_values
        }).then(function(response) {
            return { id: response.data.id };
        });
        return promise;
    }

    function editArtifact(artifact_id, field_values) {
        return rest.one('artifacts', artifact_id).customPUT({
            values : field_values
        }).then (function() {
            return { id: artifact_id };
        });
    }

    function responseInterceptor(data) {
        service.error.is_error = false;
        return data;
    }

    function errorInterceptor(response) {
        var error_message;
        if (response.data && response.data.error) {
            error_message = response.data.error.message;
        } else {
            error_message = response.status +' '+ response.statusText;
        }
        service.error = {
            is_error      : true,
            error_message : error_message
        };
        return true;
    }
}
