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

    var awkward_fields_for_creation = ['aid', 'atid', 'lud', 'burndown', 'priority', 'subby', 'subon', 'computed', 'cross', 'file', 'tbl', 'perm'];

    var service = {
        error: {
            is_error     : false,
            error_message: null
        },

        isLoading               : false,
        createArtifact          : createArtifact,
        reorderFieldsInGoodOrder: reorderFieldsInGoodOrder,
        getArtifactsTitles      : getArtifactsTitles,
        getTrackerArtifacts     : getTrackerArtifacts,
        getTrackerStructure     : getTrackerStructure
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

    function reorderFieldsInGoodOrder(complete_tracker_structure) {
        var structure      = complete_tracker_structure.structure,
            ordered_fields = [];

        for (var i = 0; i < structure.length; i++) {
            ordered_fields.push(getCompleteField(structure[i], complete_tracker_structure.fields));
        }

        return _.compact(ordered_fields);
    }

    /**
     * Return a field with two additional attributes:
     *     - content     : {array} of fields
     *     - template_url: {string} angular tamplated used to render the field
     */
    function getCompleteField(structure_field, all_fields) {
        var complete_field = _(all_fields).find({ field_id: structure_field.id });

        if (_.contains(awkward_fields_for_creation, complete_field.type)) {
            return false;
        }

        complete_field.template_url = 'field-' + complete_field.type + '.tpl.html';

        if (structure_field.content != null) {
            complete_field.content = [];

            for (var i = 0; i < structure_field.content.length; i++) {
                complete_field.content.push(getCompleteField(structure_field.content[i], all_fields));
            }

            complete_field.content = _.compact(complete_field.content);
        }

        return complete_field;
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
