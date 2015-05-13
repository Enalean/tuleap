angular
    .module('modal')
    .factory('ModalTuleapFactory', ModalTuleapFactory);

ModalTuleapFactory.$inject = ['Restangular'];

function ModalTuleapFactory(Restangular) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
        RestangularConfigurer.addRequestInterceptor(requestInterceptor);
        RestangularConfigurer.addResponseInterceptor(responseInterceptor);
        RestangularConfigurer.setErrorInterceptor(errorInterceptor);
    });

    var service = {
        error: {
            is_error      : false,
            error_message : null
        },

        isLoading           : false,
        createArtifact      : createArtifact,
        getArtifactsTitles  : getArtifactsTitles,
        getTrackerArtifacts : getTrackerArtifacts,
        getTrackerStructure : getTrackerStructure
    };
    return service;

    function getTrackerStructure(tracker_id) {
        return rest.one('trackers', tracker_id)
            .get().then(function(response) {
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

    function requestInterceptor(data) {
        service.is_loading = true;
        return data;
    }

    function responseInterceptor(data) {
        service.is_loading     = false;
        service.error.is_error = false;
        return data;
    }

    function errorInterceptor(response) {
        var error_message;
        if (response.data && response.data.error) {
            error_message = response.data.error.code +' '+response.data.error.message;
        } else {
            error_message = response.status +' '+ response.statusText;
        }
        service.error = {
            is_error      : true,
            error_message : error_message
        };
        service.is_loading = false;
        return true;
    }
}
