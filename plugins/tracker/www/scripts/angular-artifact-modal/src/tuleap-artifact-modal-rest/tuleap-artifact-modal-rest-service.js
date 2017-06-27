angular
    .module('tuleap-artifact-modal-rest')
    .service('TuleapArtifactModalRestService', TuleapArtifactModalRestService);

TuleapArtifactModalRestService.$inject = [
    '$q',
    'Restangular'
];

function TuleapArtifactModalRestService(
    $q,
    Restangular
) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
        RestangularConfigurer.addResponseInterceptor(responseInterceptor);
        RestangularConfigurer.setErrorInterceptor(errorInterceptor);
    });

    var self = this;
    _.extend(self, {
        createArtifact           : createArtifact,
        editArtifact             : editArtifact,
        getAllOpenParentArtifacts: getAllOpenParentArtifacts,
        getArtifact              : getArtifact,
        getArtifactFieldValues   : getArtifactFieldValues,
        getFileUploadRules       : getFileUploadRules,
        getFollowupsComments     : getFollowupsComments,
        getOpenParentArtifacts   : getOpenParentArtifacts,
        getTracker               : getTracker,
        getUserPreference        : getUserPreference,
        searchUsers              : searchUsers,
        uploadAdditionalChunk    : uploadAdditionalChunk,
        uploadTemporaryFile      : uploadTemporaryFile,

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

    function getOpenParentArtifacts(tracker_id, limit, offset) {
        return rest.one('trackers', tracker_id)
            .all('parent_artifacts')
            .getList({
                limit : limit,
                offset: offset
            }).then(function(response) {
                var result = {
                    results: response.data,
                    total  : response.headers('X-PAGINATION-SIZE')
                };

                return result;
            });
    }

    function getAllOpenParentArtifacts(tracker_id, limit, offset) {
        var deferred = $q.defer();

        self.getOpenParentArtifacts(tracker_id, limit, offset)
        .then(function(response) {
            if (response.total <= offset + limit) {
                deferred.resolve(response.results);
            } else {
                var promise = self.getAllOpenParentArtifacts(tracker_id, limit, offset + limit)
                .then(function(sub_response) {
                    return response.results.concat(sub_response);
                });

                deferred.resolve(promise);
            }
        }, function(error) {
            deferred.reject(error);
        });

        return deferred.promise;
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
