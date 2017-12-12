import {
    get,
    recursiveGet,
    put,
    post,
    options
} from 'tlp';

export default RestService;

RestService.$inject = ['$q'];

function RestService($q) {
    const headers = {
        "content-type": "application/json"
    };

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
        return $q.when(get(`/api/v1/trackers/${ tracker_id }`, {
            cache: 'force-cache'
        })).then(responseHandler, errorHandler);
    }

    function getArtifact(artifact_id) {
        return $q.when(get(`/api/v1/artifacts/${ artifact_id }`))
            .then(responseHandler, errorHandler);
    }

    function getArtifactFieldValues(artifact_id) {
        return getArtifact(artifact_id).then(artifact => {
            const {
                values = []
            } = artifact;

            const indexed_values = {};

            for (const value of values) {
                indexed_values[value.field_id] = value;
            }

            indexed_values.title  = artifact.title;

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
        const body  = JSON.stringify({
            tracker: {
                id: tracker_id
            },
            values: field_values
        });

        return $q.when(post('/api/v1/artifacts', {
            headers,
            body
        })).then(response => {
            return responseHandler(response).then(({ id }) => { return { id }; });
        }, errorHandler);
    }

    function editArtifact(artifact_id, field_values, followup_comment) {
        const body  = JSON.stringify({
            values : field_values,
            comment: followup_comment
        });

        return $q.when(put(`/api/v1/artifacts/${ artifact_id }`, {
            headers,
            body
        })).then(() => {
            return { id: artifact_id };
        }, errorHandler);
    }

    function searchUsers(query) {
        return $q.when(get('/api/v1/users', {
            params: { query }
        })).then(responseHandler, errorHandler);
    }

    function getFollowupsComments(artifact_id, limit, offset, order) {
        return $q.when(get(`/api/v1/artifacts/${ artifact_id }/changesets`, {
            params: {
                fields: 'comments',
                limit,
                offset,
                order
            }
        })).then(response => {
            return responseHandler(response).then(followup_comments => {
                return {
                    results: followup_comments,
                    total  : response.headers.get('X-PAGINATION-SIZE')
                };
            });
        }, errorHandler);
    }

    function uploadTemporaryFile(file_to_upload, description) {
        const body  = JSON.stringify({
            name    : file_to_upload.filename,
            mimetype: file_to_upload.filetype,
            content : file_to_upload.chunks[0],
            description
        });

        return $q.when(post('/api/v1/artifact_temporary_files', {
            headers,
            body
        })).then(response => {
            return responseHandler(response).then(({ id }) => id);
        }, errorHandler);
    }

    function uploadAdditionalChunk(temporary_file_id, chunk, chunk_offset) {
        const params = JSON.stringify({
            content: chunk,
            offset : chunk_offset
        });

        return $q.when(put(`/api/v1/artifact_temporary_files/${ temporary_file_id }`, params))
            .then(responseHandler, errorHandler);
    }

    function getUserPreference(user_id, preference_key) {
        const params = {
            key: preference_key
        };

        return $q.when(get(`/api/v1/users/${ user_id }/preferences`, {
            cache: 'force-cache',
            params
        })).then(responseHandler, errorHandler);
    }

    function getFileUploadRules() {
        return $q.when(options('/api/v1/artifact_temporary_files')).then(response => {
            const disk_quota     = parseInt(response.headers.get('X-QUOTA'), 10);
            const disk_usage     = parseInt(response.headers.get('X-DISK-USAGE'), 10);
            const max_chunk_size = parseInt(response.headers.get('X-UPLOAD-MAX-FILE-CHUNKSIZE'), 10);

            return {
                disk_quota,
                disk_usage,
                max_chunk_size
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

    function responseHandler(response) {
        self.error.is_error = false;
        return $q.when(response.json());
    }

    function errorHandler(error) {
        self.error.is_error = true;
        return $q.when(error.response.json()).then(error_json => {
            if (error_json !== undefined && error_json.error && error_json.error.message) {
                self.error.error_message = error_json.error.message;
            } else {
                self.error.error_message = error.response.status + ' ' + error.response.statusText;
            }
            return $q.reject();
        });
    }
}
