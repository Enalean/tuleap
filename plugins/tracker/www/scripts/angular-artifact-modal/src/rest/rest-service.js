import {
    get,
    recursiveGet,
    put,
    post,
    options
} from 'tlp';

import {
    resetError,
    setError
} from './rest-error-state.js';

export {
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
    getFirstReverseIsChildLink
};

const headers = {
    "content-type": "application/json"
};

function getTracker(tracker_id) {
    return get(`/api/v1/trackers/${ tracker_id }`)
        .then(responseHandler, errorHandler);
}

function getArtifact(artifact_id) {
    return get(`/api/v1/artifacts/${ artifact_id }`)
        .then(responseHandler, errorHandler);
}

async function getArtifactFieldValues(artifact_id) {
    const artifact = await getArtifact(artifact_id);

    const {
        values = []
    } = artifact;

    const indexed_values = {};

    for (const value of values) {
        indexed_values[value.field_id] = value;
    }

    indexed_values.title  = artifact.title;

    return indexed_values;
}

async function getAllOpenParentArtifacts(tracker_id, limit, offset) {
    try {
        const parent_artifacts = await recursiveGet(`/api/v1/trackers/${ tracker_id }/parent_artifacts`, {
            params: {
                limit,
                offset
            }
        });
        resetError();
        return parent_artifacts;
    } catch (error) {
        return errorHandler(error);
    }
}

async function createArtifact(tracker_id, field_values) {
    const body  = JSON.stringify({
        tracker: {
            id: tracker_id
        },
        values: field_values
    });

    try {
        const response = await post('/api/v1/artifacts', {
            headers,
            body
        });
        const { id } = await responseHandler(response);
        return { id };
    } catch (error) {
        return errorHandler(error);
    }
}

async function editArtifact(artifact_id, field_values, followup_comment) {
    const body  = JSON.stringify({
        values : field_values,
        comment: followup_comment
    });

    try {
        await put(`/api/v1/artifacts/${ artifact_id }`, {
            headers,
            body
        });
        resetError();
        return { id: artifact_id };
    } catch (error) {
        return errorHandler(error);
    }
}

async function searchUsers(query) {
    try {
        const response = await get('/api/v1/users', {
            params: { query }
        });
        const results = await responseHandler(response);
        return { results };
    } catch (error) {
        return errorHandler(error);
    }
}

async function getFollowupsComments(artifact_id, limit, offset, order) {
    try {
        const response = await get(`/api/v1/artifacts/${ artifact_id }/changesets`, {
            params: {
                fields: 'comments',
                limit,
                offset,
                order
            }
        });
        const followup_comments = await responseHandler(response);
        return {
            results: followup_comments,
            total  : response.headers.get('X-PAGINATION-SIZE')
        };
    } catch (error) {
        return errorHandler(error);
    }
}

async function uploadTemporaryFile(file_to_upload, description) {
    const body  = JSON.stringify({
        name    : file_to_upload.filename,
        mimetype: file_to_upload.filetype,
        content : file_to_upload.chunks[0],
        description
    });

    try {
        const response = await post('/api/v1/artifact_temporary_files', {
            headers,
            body
        });
        const { id } = await responseHandler(response);
        return id;
    } catch (error) {
        return errorHandler(error);
    }
}

function uploadAdditionalChunk(temporary_file_id, chunk, chunk_offset) {
    const params = JSON.stringify({
        content: chunk,
        offset : chunk_offset
    });

    return put(`/api/v1/artifact_temporary_files/${ temporary_file_id }`, params)
        .catch(errorHandler);
}

function getUserPreference(user_id, preference_key) {
    return get(`/api/v1/users/${ user_id }/preferences`, {
        cache : 'force-cache',
        params: {
            key: preference_key
        }
    }).then(responseHandler, errorHandler);
}

async function getFileUploadRules() {
    const response       = await options('/api/v1/artifact_temporary_files');
    const disk_quota     = parseInt(response.headers.get('X-QUOTA'), 10);
    const disk_usage     = parseInt(response.headers.get('X-DISK-USAGE'), 10);
    const max_chunk_size = parseInt(response.headers.get('X-UPLOAD-MAX-FILE-CHUNKSIZE'), 10);

    return {
        disk_quota,
        disk_usage,
        max_chunk_size
    };
}

async function getFirstReverseIsChildLink(artifact_id) {
    try {
        const response = await get(`/api/v1/artifacts/${ artifact_id }/linked_artifacts`, {
            params: {
                direction: 'reverse',
                nature   : '_is_child',
                limit    : 1,
                offset   : 0
            }
        });
        const { collection } = await responseHandler(response);
        return collection;
    } catch (error) {
        return errorHandler(error);
    }
}

async function responseHandler(response) {
    resetError();
    return await response.json();
}

async function errorHandler(error) {
    const error_json = await error.response.json();
    if (error_json !== undefined && error_json.error && error_json.error.message) {
        setError(error_json.error.message);
        return Promise.reject();
    }
    setError(error.response.status + ' ' + error.response.statusText);
    return Promise.reject();
}
