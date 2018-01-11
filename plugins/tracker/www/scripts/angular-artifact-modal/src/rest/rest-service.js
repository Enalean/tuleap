import _ from 'lodash';

import {
    get,
    recursiveGet
} from 'tlp';

export default RestService;

RestService.$inject = [
    '$q',
    'Restangular'
];

function RestService(
    $q,
    Restangular
) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
        RestangularConfigurer.addResponseInterceptor(responseInterceptor);
        RestangularConfigurer.setErrorInterceptor(errorInterceptor);
    });

    const self = this;
    Object.assign(self, {
        createArtifact,
        editArtifact,
        getAllOpenParentArtifacts,
        getArtifact,
        getArtifactFieldValues,
        getFileUploadRules,
        getFollowupsComments,
        getTracker,
        getUserPreference,
        searchUsers,
        uploadAdditionalChunk,
        uploadTemporaryFile,
        getFirstReverseIsChildLink,

        error: {
            is_error     : false,
            error_message: null
        }
    });

    function getTracker(tracker_id) {
        return rest.one('trackers', tracker_id)
            .withHttpConfig({
                cache: true
            }).get().then(function(response) {
                return response.data;
            });
    }

    function getArtifact(artifact_id) {
        return rest.one('artifacts', artifact_id)
            .get().then(function(response) {
                return response.data;
            });
    }

    function getArtifactFieldValues(artifact_id) {
        return self.getArtifact(artifact_id)
            .then(function(artifact) {
                var artifact_values = (artifact && artifact.values) ? artifact.values : [];
                var indexed_values  = _.indexBy(artifact_values, function(val) {
                    return val.field_id;
                });
                indexed_values.title = artifact.title;

                return indexed_values;
            });
    }

    function getAllOpenParentArtifacts(tracker_id, limit, offset) {
        const route = `/api/v1/trackers/${ tracker_id }/parent_artifacts`;
        return $q.when(recursiveGet(route, {
            params: {
                limit,
                offset
            }
        })).then(parent_artifacts => {
            self.error.is_error = false;
            return parent_artifacts;
        }, errorHandler);
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

    function editArtifact(artifact_id, field_values, followup_comment) {
        return rest.one('artifacts', artifact_id).customPUT({
            values : field_values,
            comment: followup_comment
        }).then(function() {
            return { id: artifact_id };
        });
    }

    function searchUsers(query) {
        return rest.all('users').getList({
            query: query
        }).then(function(response) {
            return response.data;
        });
    }

    function getFollowupsComments(artifact_id, limit, offset, order) {
        return rest
            .one('artifacts', artifact_id)
            .all('changesets')
            .getList({
                fields : 'comments',
                limit  : limit,
                offset : offset,
                order  : order
            }).then(function(response) {
                var result = {
                    results: response.data,
                    total  : response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function uploadTemporaryFile(file_to_upload, description) {
        return rest
            .service('artifact_temporary_files')
            .post({
                name: file_to_upload.filename,
                mimetype: file_to_upload.filetype,
                content: file_to_upload.chunks[0],
                description: description
            }).then(function(response) {
                return response.data.id;
            });
    }

    function uploadAdditionalChunk(temporary_file_id, chunk, chunk_offset) {
        return rest
            .one('artifact_temporary_files', temporary_file_id)
            .customPUT({
                content: chunk,
                offset: chunk_offset
            });
    }

    function getUserPreference(user_id, preference_key) {
        return rest
            .one('users', user_id)
            .withHttpConfig({
                cache: true
            })
            .customGET('preferences', {
                key: preference_key
            }).then(function(response) {
                return response.data;
            });
    }

    function getFileUploadRules() {
        return rest
            .one('artifact_temporary_files')
            .options()
            .then(function(response) {
                var disk_quota     = parseInt(response.headers('X-QUOTA'), 10);
                var disk_usage     = parseInt(response.headers('X-DISK-USAGE'), 10);
                var max_chunk_size = parseInt(response.headers('X-UPLOAD-MAX-FILE-CHUNKSIZE'), 10);

                return {
                    disk_quota    : disk_quota,
                    disk_usage    : disk_usage,
                    max_chunk_size: max_chunk_size
                };
            });
    }

    function getFirstReverseIsChildLink(artifact_id) {
        return $q.when(get(`/api/v1/artifacts/${ artifact_id }/linked_artifacts`, {
            params: {
                direction: 'reverse',
                nature   : '_is_child',
                limit    : 1,
                offset   : 0
            }
        })).then(response => {
            self.error.is_error = false;
            return $q.when(response.json()).then(({ collection }) => {
                return collection;
            });
        }, errorHandler);
    }

    function errorHandler(error) {
        self.error.is_error = true;
        return $q.when(error.response.json()).then(error_json => {
            if (error_json.error && error_json.error.message) {
                self.error.error_message = error_json.error.message;
            } else {
                self.error.error_message = error.response.status + ' ' + error.response.statusText;
            }
            return $q.reject();
        });
    }

    function responseInterceptor(data) {
        self.error.is_error = false;
        return data;
    }

    function errorInterceptor(response) {
        var error_message;
        if (response.data && response.data.error) {
            error_message = response.data.error.message;
        } else {
            error_message = response.status + ' ' + response.statusText;
        }
        self.error = {
            is_error      : true,
            error_message : error_message
        };
        return true;
    }
}
