import rest_module from './rest.js';
import angular     from 'angular';
import tlp         from 'tlp';
import 'angular-mocks';

describe("TuleapArtifactModalRestService", () => {
    let $q,
        RestService;

    beforeEach(() => {
        angular.mock.module(rest_module);

        angular.mock.inject(function(
            _$q_,
            _TuleapArtifactModalRestService_
        ) {
            $q          = _$q_;
            RestService = _TuleapArtifactModalRestService_;
        });

        spyOn(tlp, "recursiveGet");
        spyOn(tlp, "get");
        spyOn(tlp, "post");
        spyOn(tlp, "put");
        spyOn(tlp, "options");

        installPromiseMatchers();
    });

    function mockFetchSuccess(spy_function, { headers, return_json } = {}) {
        spy_function.and.returnValue($q.when({
            headers,
            json: () => $q.when(return_json)
        }));
    }

    function mockFetchError(spy_function, { status, statusText, error_json } = {}) {
        spy_function.and.returnValue($q.reject({
            response: {
                status,
                statusText,
                json: () => $q.when(error_json)
            }
        }));
    }

    it("getTracker() - Given a tracker id, when I get the tracker, then a promise will be resolved with the tracker", () => {
        const return_json = {
            id   : 84,
            label: "Functionize recklessly"
        };
        mockFetchSuccess(tlp.get, { return_json });

        const promise = RestService.getTracker(84);

        expect(promise).toBeResolvedWith({
            id   : 84,
            label: "Functionize recklessly"
        });
        expect(tlp.get).toHaveBeenCalledWith('/api/v1/trackers/84', {
            cache: 'force-cache'
        });
    });

    it("getArtifact() - Given an artifact id, when I get the artifact, then a promise will be resolved with an artifact object", (done) => {
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

        const promise = RestService.getArtifact(792).then(artifact => {
            expect(artifact).toEqual(return_json);
            done();
        });
        expect(promise).toBeResolved();
        expect(tlp.get).toHaveBeenCalledWith('/api/v1/artifacts/792');
    });

    it("getArtifactFieldValues() - given an artifact id, when I get the artifact's field values, then a promise will be resolved with a map of field values indexed by their field id", () => {
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

        const promise = RestService.getArtifactFieldValues(40);

        expect(promise).toBeResolvedWith({
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
        it("Given the id of a child tracker, when I get all the open parents for this tracker, then a promise will be resolved with the artifacts", () => {
            const tracker_id = 49;
            const limit      = 30;
            const offset     = 0;
            const artifacts  = [
                { id: 21, title: 'equationally' },
                { id: 82, title: 'brachiator' }
            ];
            tlp.recursiveGet.and.returnValue(artifacts);

            const promise = RestService.getAllOpenParentArtifacts(tracker_id, limit, offset);

            expect(promise).toBeResolvedWith(artifacts);
            expect(tlp.recursiveGet).toHaveBeenCalledWith('/api/v1/trackers/49/parent_artifacts', {
                params: {
                    limit,
                    offset
                }
            });
        });

        it("When there is a REST error, then it will be shown", () => {
            const tracker_id = 12;
            const limit      = 30;
            const offset     = 0;
            const error_json = {
                error: {
                    message: 'No you cannot'
                }
            };

            mockFetchError(tlp.recursiveGet, { error_json });

            const promise = RestService.getAllOpenParentArtifacts(tracker_id, limit, offset);

            expect(promise).toBeRejected();
            expect(RestService.error.error_message).toEqual('No you cannot');
        });
    });

    describe("searchUsers() -", () => {
        it("Given a query, when I search for a username containing the query, then a promise will be resolved with an array of user representations", () => {
            const return_json = [
                { id: 629, label: "Blue" },
                { id: 593, label: "Blurred" }
            ];
            mockFetchSuccess(tlp.get, { return_json });

            const promise = RestService.searchUsers("Blu");

            expect(promise).toBeResolved();
            const [first_user, second_user] = promise.$$state.value;
            expect(first_user).toEqual({ id: 629, label: "Blue" });
            expect(second_user).toEqual({ id: 593, label: "Blurred" });
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/users', {
                params: { query: "Blu" }
            });
        });
    });

    describe("createArtifact() -", () => {
        it("Given a tracker id and an array of fields containing their id and selected values, when I create an artifact, then the field values will be sent using the artifact creation REST route and a promise will be resolved with the new artifact's id", () => {
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

            const promise = RestService.createArtifact(3, field_values);

            expect(promise).toBeResolvedWith({ id: 286 });
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

        it("When I create an artifact and the server responds an error with data, then the service's error will be set with the data's code and message and a promise will be rejected", () => {
            const error_json = {
                error: {
                    code   : 400,
                    message: "Bad Request: error: Le champ I want to (i_want_to) est obligatoire."
                }
            };
            mockFetchError(tlp.post, { error_json });

            const promise = RestService.createArtifact();

            expect(promise).toBeRejected();
            expect(RestService.error.error_message).toEqual("Bad Request: error: Le champ I want to (i_want_to) est obligatoire.");
        });

        it("Given the server didn't respond, when I create an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", () => {
            mockFetchError(tlp.post, {
                status    : 404,
                statusText: 'Not Found'
            });

            const promise = RestService.createArtifact();

            expect(promise).toBeRejected();
            expect(RestService.error.error_message).toEqual("404 Not Found");
        });
    });

    describe("getFollowupsComments() -", () => {
        it("Given an artifact id, a limit, an offset and an order, when I get the artifact's followup comments, then a promise will be resolved with an object containing the comments in a 'results' property and the total number of comments in a 'total' property", () => {
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

            const promise = RestService.getFollowupsComments(148, 66, 23, 'desc');

            expect(promise).toBeResolved();
            const followup_comments = promise.$$state.value;

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
        it("Given a file object with a filename, a filetype and a chunks array and given a description, when I upload a new temporary file, then a promise will be resolved with the new temporary file's id", () => {
            mockFetchSuccess(tlp.post, { return_json: { id: 4 } });

            const file_to_upload = {
                filename: "bitterheartedness",
                filetype: "image/png",
                chunks  : [
                    "FwnCeTwZcgBOiH"
                ]
            };
            const description = "bullboat metrosteresis classicality";

            const promise = RestService.uploadTemporaryFile(file_to_upload, description);

            expect(promise).toBeResolvedWith(4);
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
                });
        });
    });

    describe("uploadAdditionalChunk() -", () => {
        it("Given a temporary file id, a chunk and a chunk offset, when I upload an additional chunk to be appended to a temporary file, then a promise will be resolved", () => {
            mockFetchSuccess(tlp.put);

            const promise = RestService.uploadAdditionalChunk(9, "rmNcNnltd", 4);

            expect(promise).toBeResolved();
            expect(tlp.put).toHaveBeenCalledWith('/api/v1/artifact_temporary_files/9',
                JSON.stringify({
                    content: "rmNcNnltd",
                    offset : 4
                }));
        });
    });

    describe("getUserPreference() -", () => {
        it(" Given a key, when I search for a preference, then a promise will be resolved with an object of user preference representation", () => {
            const return_json = {
                key  : 'tracker_comment_invertorder_93',
                value: '1'
            };
            mockFetchSuccess(tlp.get, { return_json });

            const promise = RestService.getUserPreference(102, 'tracker_comment_invertorder_93');

            expect(promise).toBeResolvedWith(return_json);
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/users/102/preferences', {
                cache : 'force-cache',
                params: {
                    key: 'tracker_comment_invertorder_93'
                }
            });
        });
    });

    describe("editArtifact() -", () => {
        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", () => {
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

            const promise = RestService.editArtifact(8354, field_values, followup_comment);

            expect(promise).toBeResolvedWith({
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

        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact with a comment, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", () => {
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

            const promise = RestService.editArtifact(8354, field_values, followup_comment);

            expect(promise).toBeResolvedWith({
                id: 8354
            });
        });

        it("Given the server didn't respond, when I edit an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", () => {
            mockFetchError(tlp.put, {
                status    : 404,
                statusText: 'Not Found'
            });

            const promise = RestService.editArtifact(6144);

            expect(promise).toBeRejected();
            expect(RestService.error.error_message).toEqual("404 Not Found");
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

            const promise = RestService.getFileUploadRules();

            expect(promise).toBeResolvedWith({
                disk_quota    : 2229535,
                disk_usage    : 596878,
                max_chunk_size: 732798
            });
            expect(tlp.options).toHaveBeenCalledWith('/api/v1/artifact_temporary_files');
        });
    });

    describe("getFirstReverseIsChildLink() -", () => {
        it("Given an artifact id, then an array containing the first reverse _is_child linked artifact will be returned", () => {
            const artifact_id = 20;
            const collection  = [{ id: 46 }];
            mockFetchSuccess(tlp.get, {
                return_json: { collection }
            });

            const promise = RestService.getFirstReverseIsChildLink(artifact_id);

            expect(promise).toBeResolvedWith(collection);
            expect(tlp.get).toHaveBeenCalledWith('/api/v1/artifacts/20/linked_artifacts', {
                params: {
                    direction: 'reverse',
                    nature   : '_is_child',
                    limit    : 1,
                    offset   : 0
                }
            });
        });

        it("Given an artifact id and given there weren't any linked _is_child artifacts, then an empty array will be returned", () => {
            const artifact_id = 78;
            const collection  = [];
            mockFetchSuccess(tlp.get, {
                return_json: { collection }
            });

            const promise = RestService.getFirstReverseIsChildLink(artifact_id);

            expect(promise).toBeResolvedWith([]);
        });

        it("When there is a REST error, then it will be shown", () => {
            const artifact_id = 9;
            const error_json  = {
                error: {
                    message: 'Invalid artifact id'
                }
            };
            mockFetchError(tlp.get, { error_json });

            const promise = RestService.getFirstReverseIsChildLink(artifact_id);

            expect(promise).toBeRejected();
            expect(RestService.error.error_message).toEqual('Invalid artifact id');
        });
    });
});
