import {
    tlp,
    mockFetchSuccess,
    mockFetchError
} from 'tlp-mocks';
import * as RestService from './rest-service.js';
import {
    rewire$setError,
    restore as restoreErrorState
} from './rest-error-state.js';

describe("rest-service", () => {
    let setError;

    beforeEach(() => {
        setError = jasmine.createSpy("setError");
        rewire$setError(setError);
    });

    afterEach(() => {
        restoreErrorState();
    });

    it("getTracker() - Given a tracker id, when I get the tracker, then a promise will be resolved with the tracker", async () => {
        const return_json = {
            id   : 84,
            label: "Functionize recklessly"
        };
        mockFetchSuccess(tlp.get, { return_json });

        const tracker = await RestService.getTracker(84);

        expect(tracker).toEqual({
            id   : 84,
            label: "Functionize recklessly"
        });
        expect(tlp.get).toHaveBeenCalledWith('/api/v1/trackers/84');
    });

    it("getArtifact() - Given an artifact id, when I get the artifact, then a promise will be resolved with an artifact object", async () => {
        const return_json = {
            id    : 792,
            values: [
                {
                    field_id: 74,
                    label   : "Kartvel",
                    value   : "ruralize"
                }, {
                    field_id      : 31,
                    label         : "xenium",
                    bind_value_ids: [96, 81]
                }
            ]
        };
        mockFetchSuccess(tlp.get, { return_json });

        const artifact = await RestService.getArtifact(792);

        expect(artifact).toEqual(return_json);
        expect(tlp.get).toHaveBeenCalledWith('/api/v1/artifacts/792');
    });

    it("getArtifactFieldValues() - given an artifact id, when I get the artifact's field values, then a promise will be resolved with a map of field values indexed by their field id", async () => {
        const return_json = {
            id    : 40,
            values: [
                {
                    field_id: 866,
                    label   : "unpredisposed",
                    value   : "ectogenous"
                }, {
                    field_id: 468,
                    label   : "coracler",
                    value   : "caesaropapism"
                }
            ],
            title: 'coincoin'
        };
        mockFetchSuccess(tlp.get, { return_json });

        const values = await RestService.getArtifactFieldValues(40);

        expect(values).toEqual({
            866: {
                field_id: 866,
                label   : "unpredisposed",
                value   : "ectogenous"
            },
            468: {
                field_id: 468,
                label   : "coracler",
                value   : "caesaropapism"
            },
            title: 'coincoin'
        });
    });

    describe("getAllOpenParentArtifacts() -", () => {
        it("Given the id of a child tracker, when I get all the open parents for this tracker, then a promise will be resolved with the artifacts", async () => {
            const tracker_id = 49;
            const limit      = 30;
            const offset     = 0;
            const artifacts  = [
                { id: 21, title: 'equationally' },
                { id: 82, title: 'brachiator' }
            ];
            tlp.recursiveGet.and.returnValue(artifacts);

            const values = await RestService.getAllOpenParentArtifacts(tracker_id, limit, offset);

            expect(values).toEqual(artifacts);
            expect(tlp.recursiveGet).toHaveBeenCalledWith('/api/v1/trackers/49/parent_artifacts', {
                params: {
                    limit,
                    offset
                }
            });
        });

        it("When there is a REST error, then it will be shown", async () => {
            const tracker_id = 12;
            const limit      = 30;
            const offset     = 0;
            const error_json = {
                error: {
                    message: 'No you cannot'
                }
            };

            mockFetchError(tlp.recursiveGet, { error_json });

            await RestService.getAllOpenParentArtifacts(tracker_id, limit, offset).then(
                () => Promise.reject(new Error("Promise should be rejected")),
                () => {
                    expect(setError).toHaveBeenCalledWith('No you cannot');
                });
        });
    });

    describe("searchUsers() -", () => {
        it("Given a query, when I search for a username containing the query, then a promise will be resolved with an array of user representations", async () => {
            const return_json = [
                { id: 629, label: "Blue" },
                { id: 593, label: "Blurred" }
            ];
            mockFetchSuccess(tlp.get, { return_json });

            const { results: [first_user, second_user] } = await RestService.searchUsers("Blu");

            expect(first_user).toEqual({ id: 629, label: "Blue" });
            expect(second_user).toEqual({ id: 593, label: "Blurred" });
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/users', {
                params: { query: "Blu" }
            });
        });
    });

    describe("createArtifact() -", () => {
        it("Given a tracker id and an array of fields containing their id and selected values, when I create an artifact, then the field values will be sent using the artifact creation REST route and a promise will be resolved with the new artifact's id", async () => {
            const return_json = {
                id     : 286,
                tracker: {
                    id   : 3,
                    label: "Enkidu slanderfully"
                }
            };
            const field_values = [
                { field_id: 38, value: "fingerroot" },
                { field_id: 140, bind_value_ids: [253]}
            ];
            mockFetchSuccess(tlp.post, { return_json });

            const { id } = await RestService.createArtifact(3, field_values);

            expect(id).toEqual(286);

            expect(tlp.post).toHaveBeenCalledWith('/api/v1/artifacts', {
                headers: {
                    'content-type': 'application/json'
                },
                body: JSON.stringify({
                    tracker: {
                        id: 3
                    },
                    values: field_values
                })
            });
        });

        it("When I create an artifact and the server responds an error with data, then the service's error will be set with the data's code and message and a promise will be rejected", async () => {
            const error_json = {
                error: {
                    code   : 400,
                    message: "Bad Request: error: Le champ I want to (i_want_to) est obligatoire."
                }
            };
            mockFetchError(tlp.post, { error_json });

            await RestService.createArtifact().then(
                () => Promise.reject(new Error("Promise should be rejected")),
                () => {
                    expect(setError).toHaveBeenCalledWith("Bad Request: error: Le champ I want to (i_want_to) est obligatoire.");
                });
        });

        it("Given the server didn't respond, when I create an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", async () => {
            mockFetchError(tlp.post, {
                status    : 404,
                statusText: 'Not Found'
            });

            await RestService.createArtifact().then(
                () => Promise.reject(new Error("Promise should be rejected")),
                () => {
                    expect(setError).toHaveBeenCalledWith("404 Not Found");
                });
        });
    });

    describe("getFollowupsComments() -", () => {
        it("Given an artifact id, a limit, an offset and an order, when I get the artifact's followup comments, then a promise will be resolved with an object containing the comments in a 'results' property and the total number of comments in a 'total' property", async () => {
            const return_json = [
                {
                    id          : 629,
                    last_comment: {
                        body: "orometer",
                        format: "text"
                    }
                }, {
                    id          : 593,
                    last_comment: {
                        body: "mystagogic",
                        format: "html"
                    }
                }
            ];
            const [first_response, second_response] = return_json;

            mockFetchSuccess(tlp.get, {
                headers: {
                    /** 'X-PAGINATION-SIZE' */
                    get: () => 74
                },
                return_json
            });

            const followup_comments = await RestService.getFollowupsComments(148, 66, 23, 'desc');

            expect(followup_comments.total).toEqual(74);
            expect(followup_comments.results[0]).toEqual(first_response);
            expect(followup_comments.results[1]).toEqual(second_response);
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/artifacts/148/changesets', {
                params: {
                    fields: 'comments',
                    limit : 66,
                    offset: 23,
                    order : 'desc'
                }
            });
        });
    });

    describe("uploadTemporaryFile() -", () => {
        it("Given a file object with a filename, a filetype and a chunks array and given a description, when I upload a new temporary file, then a promise will be resolved with the new temporary file's id", async () => {
            mockFetchSuccess(tlp.post, { return_json: { id: 4 } });

            const file_to_upload = {
                filename: "bitterheartedness",
                filetype: "image/png",
                chunks  : [
                    "FwnCeTwZcgBOiH"
                ]
            };
            const description = "bullboat metrosteresis classicality";

            const file_upload = await RestService.uploadTemporaryFile(file_to_upload, description);

            expect(file_upload).toEqual(4);
            expect(tlp.post).toHaveBeenCalledWith('/api/v1/artifact_temporary_files',
                {
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        name       : "bitterheartedness",
                        mimetype   : "image/png",
                        content    : "FwnCeTwZcgBOiH",
                        description: "bullboat metrosteresis classicality"
                    })
                }
            );
        });
    });

    describe("uploadAdditionalChunk() -", () => {
        it("Given a temporary file id, a chunk and a chunk offset, when I upload an additional chunk to be appended to a temporary file, then a promise will be resolved", async () => {
            mockFetchSuccess(tlp.put);

            await RestService.uploadAdditionalChunk(9, "rmNcNnltd", 4);

            expect(tlp.put).toHaveBeenCalledWith("/api/v1/artifact_temporary_files/9", {
                headers: {
                    "content-type": "application/json"
                },
                body: JSON.stringify({
                    content: "rmNcNnltd",
                    offset: 4
                })
            });
        });
    });

    describe("getUserPreference() -", () => {
        it(" Given a key, when I search for a preference, then a promise will be resolved with an object of user preference representation", async () => {
            const return_json = {
                key  : 'tracker_comment_invertorder_93',
                value: '1'
            };
            mockFetchSuccess(tlp.get, { return_json });

            const result = await RestService.getUserPreference(102, 'tracker_comment_invertorder_93');

            expect(result).toEqual(return_json);
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/users/102/preferences', {
                cache : 'force-cache',
                params: {
                    key: 'tracker_comment_invertorder_93'
                }
            });
        });
    });

    describe("editArtifact() -", () => {
        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", async () => {
            const followup_comment = {
                value : '',
                format: 'text'
            };
            const field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] }
            ];
            mockFetchSuccess(tlp.put, {
                return_json: {
                    values : field_values,
                    comment: followup_comment
                }
            });

            const artifact_edition = await RestService.editArtifact(8354, field_values, followup_comment);

            expect(artifact_edition).toEqual({
                id: 8354
            });
            expect(tlp.put).toHaveBeenCalledWith('/api/v1/artifacts/8354', {
                headers: {
                    'content-type': 'application/json'
                },
                body: JSON.stringify({
                    values : field_values,
                    comment: followup_comment
                })
            });
        });

        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact with a comment, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", async () => {
            const followup_comment = {
                value: 'This is <b>my</b> comment',
                format: 'html'
            };
            const field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] }
            ];
            mockFetchSuccess(tlp.put, {
                return_json: {
                    values: field_values,
                    comment: followup_comment
                }
            });

            const artifact_edition = await RestService.editArtifact(8354, field_values, followup_comment);

            expect(artifact_edition).toEqual({
                id: 8354
            });
        });

        it("Given the server didn't respond, when I edit an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", async () => {
            mockFetchError(tlp.put, {
                status    : 404,
                statusText: 'Not Found'
            });

            await RestService.editArtifact(6144).then(
                () => Promise.reject(new Error("Promise should be rejected")),
                () => {
                    expect(setError).toHaveBeenCalledWith("404 Not Found");
                });
        });
    });

    describe("getFileUploadRules() -", () => {
        it("When I get the file upload rules, then a promise will be resolved with an object containing the disk quota for the logged user, her disk usage and the max chunk size that can be sent when uploading a file", async () => {
            const headers = {
                'X-QUOTA'                    : '2229535',
                'X-DISK-USAGE'               : '596878',
                'X-UPLOAD-MAX-FILE-CHUNKSIZE': '732798'
            };

            mockFetchSuccess(tlp.options, {
                headers: {
                    get: header => headers[header]
                }
            });

            const rules = await RestService.getFileUploadRules();

            expect(tlp.options).toHaveBeenCalledWith('/api/v1/artifact_temporary_files');
            expect(rules).toEqual({
                disk_quota    : 2229535,
                disk_usage    : 596878,
                max_chunk_size: 732798
            });
            expect(tlp.options).toHaveBeenCalledWith('/api/v1/artifact_temporary_files');
        });
    });

    describe("getFirstReverseIsChildLink() -", () => {
        it("Given an artifact id, then an array containing the first reverse _is_child linked artifact will be returned", async () => {
            const artifact_id = 20;
            const collection  = [{ id: 46 }];
            mockFetchSuccess(tlp.get, {
                return_json: { collection }
            });

            const result = await RestService.getFirstReverseIsChildLink(artifact_id);

            expect(result).toEqual(collection);
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/artifacts/20/linked_artifacts', {
                params: {
                    direction: 'reverse',
                    nature   : '_is_child',
                    limit    : 1,
                    offset   : 0
                }
            });
        });

        it("Given an artifact id and given there weren't any linked _is_child artifacts, then an empty array will be returned", async () => {
            const artifact_id = 78;
            const collection  = [];
            mockFetchSuccess(tlp.get, {
                return_json: { collection }
            });

            const result = await RestService.getFirstReverseIsChildLink(artifact_id);

            expect(result).toEqual([]);
        });

        it("When there is a REST error, then it will be shown", async () => {
            const artifact_id = 9;
            const error_json  = {
                error: {
                    message: 'Invalid artifact id'
                }
            };
            mockFetchError(tlp.get, { error_json });

            await RestService.getFirstReverseIsChildLink(artifact_id).then(
                () => Promise.reject(new Error('Promise should be rejected')),
                () => {
                    expect(setError).toHaveBeenCalledWith('Invalid artifact id');
                });
        });
    });
});
