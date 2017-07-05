describe("TuleapArtifactModalRestService", function() {
    var mockBackend, $q, deferred, TuleapArtifactModalRestService, request, response;
    beforeEach(function() {
        module('tuleap-artifact-modal-rest');

        inject(function(
            $httpBackend,
            _$q_,
            _TuleapArtifactModalRestService_
        ) {
            mockBackend = $httpBackend;
            $q = _$q_;
            TuleapArtifactModalRestService = _TuleapArtifactModalRestService_;
        });

        deferred = $q.defer();

        request  = null;
        response = null;

        installPromiseMatchers();
    });

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getTracker() - Given a tracker id, when I get the tracker, then a promise will be resolved with the tracker", function() {
        response = {
            id: 84,
            label: "Functionize recklessly"
        };

        mockBackend.expectGET('/api/v1/trackers/84').respond(JSON.stringify(response));

        var promise = TuleapArtifactModalRestService.getTracker(84);
        mockBackend.flush();

        expect(promise).toBeResolvedWith(jasmine.objectContaining({
            id: 84,
            label: "Functionize recklessly"
        }));
    });

    it("getArtifact() - Given an artifact id, when I get the artifact, then a promise will be resolved with an artifact object", function() {
        response = {
            id: 792,
            values: [
                {
                    field_id: 74,
                    label: "Kartvel",
                    value: "ruralize"
                }, {
                    field_id: 31,
                    label: "xenium",
                    bind_value_ids: [96, 81]
                }
            ]
        };
        mockBackend.expectGET('/api/v1/artifacts/792').respond(JSON.stringify(response));

        var promise = TuleapArtifactModalRestService.getArtifact(792);
        mockBackend.flush();

        expect(promise).toBeResolvedWith(jasmine.objectContaining({
            id: 792
        }));
        // workaround because jasmine.objectContaining does not seem to deal well with arrays
        var result = promise.$$state.value;
        expect(result.values).toEqual([
            {
                field_id: 74,
                label: "Kartvel",
                value: "ruralize"
            }, {
                field_id: 31,
                label: "xenium",
                bind_value_ids: [96, 81]
            }
        ]);
    });

    it("getArtifactFieldValues() - given an artifact id, when I get the artifact's field values, then a promise will be resolved with a map of field values indexed by their field id", function() {
        spyOn(TuleapArtifactModalRestService, "getArtifact").and.returnValue(deferred.promise);

        var promise = TuleapArtifactModalRestService.getArtifactFieldValues(40);
        deferred.resolve({
            id: 40,
            values: [
                {
                    field_id: 866,
                    label: "unpredisposed",
                    value: "ectogenous"
                }, {
                    field_id: 468,
                    label: "coracler",
                    value: "caesaropapism"
                }
            ],
            title: 'coincoin'
        });

        expect(promise).toBeResolvedWith({
            866: {
                field_id: 866,
                label: "unpredisposed",
                value: "ectogenous"
            },
            468: {
                field_id: 468,
                label: "coracler",
                value: "caesaropapism"
            },
            title: 'coincoin'
        });
    });

    describe("getOpenParentArtifacts() -", function() {
        it("Given a parent tracker containing 2 artifacts and given the id of the child tracker, and given a limit of 2 and an offset of 0, when I get the open parent artifacts, then a promise will be resolved with the 2 artifacts and the X-PAGINATION-SIZE header as the total", function() {
            var response = [
                { id: 433, title: "Marshalsea" },
                { id: 437, title: "scoggan" }
            ];
            mockBackend.expectGET('/api/v1/trackers/64/parent_artifacts?limit=2&offset=0').respond(
                JSON.stringify(response),
                {
                    'X-PAGINATION-SIZE': 2
                }
            );

            var promise = TuleapArtifactModalRestService.getOpenParentArtifacts(64, 2, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var data = promise.$$state.value;
            expect(data.results[0]).toEqual(jasmine.objectContaining({ id: 433, title: "Marshalsea" }));
            expect(data.results[1]).toEqual(jasmine.objectContaining({ id: 437, title: "scoggan" }));
            expect(data.results.length).toBe(2);
            expect(data.total).toEqual('2');
        });
    });

    describe("getAllOpenParentArtifacts() -", function() {
        it("Given a parent tracker containing 3 artifacts and given the id of the child tracker, and given a limit of 2 and an offset of 0, when I get all the open parent artifacts, then a promise will be resolved with the 3 artifacts", function() {
            var first_response = [
                { id: 798, title: "unbreath" },
                { id: 204, title: "eightscore" }
            ];
            var second_response = [
                { id: 45, title: "pseudocarp" }
            ];
            mockBackend.expectGET('/api/v1/trackers/91/parent_artifacts?limit=2&offset=0').respond(
                JSON.stringify(first_response),
                {
                    'X-PAGINATION-SIZE': 3
                }
            );
            mockBackend.expectGET('/api/v1/trackers/91/parent_artifacts?limit=2&offset=2').respond(
                JSON.stringify(second_response),
                {
                    'X-PAGINATION-SIZE': 3
                }
            );

            var promise = TuleapArtifactModalRestService.getAllOpenParentArtifacts(91, 2, 0);
            mockBackend.flush();

            expect(promise).toBeResolved();
            var data = promise.$$state.value;
            expect(data[0]).toEqual(jasmine.objectContaining({ id: 798, title: "unbreath" }));
            expect(data[1]).toEqual(jasmine.objectContaining({ id: 204, title: "eightscore" }));
            expect(data[2]).toEqual(jasmine.objectContaining({ id: 45, title: "pseudocarp" }));
        });

        it("Given that the first request failed, when I get all the open parent artifacts, then a promise will be rejected", function() {
            mockBackend.expectGET('/api/v1/trackers/15/parent_artifacts?limit=2&offset=0').respond(503);

            var promise = TuleapArtifactModalRestService.getAllOpenParentArtifacts(15, 2, 0);
            mockBackend.flush();

            expect(promise).toBeRejected();
        });

        it("Given that the second request failed, when I get all the open parent artifacts, then a promise will be rejected", function() {
            var response = [
                { id: 284, title: "traitorship" },
                { id: 983, title: "Pharian" }
            ];
            mockBackend.expectGET('/api/v1/trackers/55/parent_artifacts?limit=2&offset=0').respond(
                JSON.stringify(response),
                {
                    'X-PAGINATION-SIZE': 3
                }
            );
            mockBackend.expectGET('/api/v1/trackers/55/parent_artifacts?limit=2&offset=2').respond(503);

            var promise = TuleapArtifactModalRestService.getAllOpenParentArtifacts(55, 2, 0);
            mockBackend.flush();

            expect(promise).toBeRejected();
        });
    });

    describe("searchUsers() -", function() {
        it("Given a query, when I search for a username containing the query, then a promise will be resolved with an array of user representations", function() {
            var response = [
                { id: 629, label: "Blue" },
                { id: 593, label: "Blurred" }
            ];
            mockBackend.expectGET('/api/v1/users?query=Blu').respond(JSON.stringify(response));

            var promise = TuleapArtifactModalRestService.searchUsers("Blu");
            mockBackend.flush();

            // workaround because jasmine.objectContaining does not seem to deal well with arrays
            var result = promise.$$state.value;
            expect(result[0]).toEqual(jasmine.objectContaining({ id: 629, label: "Blue" }));
            expect(result[1]).toEqual(jasmine.objectContaining({ id: 593, label: "Blurred" }));
            expect(result.length).toBe(2);
        });
    });

    describe("createArtifact() -", function() {
        it("Given a tracker id and an array of fields containing their id and selected values, when I create an artifact, then the field values will be sent using the artifact creation REST route and a promise will be resolved with the new artifact's id", function() {
            // We create the artifact in the given tracker id
            var response = {
                id: 286,
                tracker: {
                    id: 3,
                    label: "Enkidu slanderfully"
                }
            };
            var field_values = [
                { field_id: 38, value: "fingerroot" },
                { field_id: 140, bind_value_ids: [253] }
            ];
            mockBackend.expectPOST('/api/v1/artifacts', {
                tracker: {
                    id: 3
                },
                values: field_values
            }).respond(JSON.stringify(response));

            var promise = TuleapArtifactModalRestService.createArtifact(3, field_values);
            mockBackend.flush();

            expect(promise).toBeResolvedWith({
                id: 286
            });
        });

        it("When I create an artifact and the server responds an error with data, then the service's error will be set with the data's code and message and a promise will be rejected", function() {
            var errorResponse = {
                error: {
                    code: 400,
                    message: "Bad Request: error: Le champ I want to (i_want_to) est obligatoire."
                }
            };
            mockBackend.expectPOST('/api/v1/artifacts').respond(400, JSON.stringify(errorResponse));

            var promise = TuleapArtifactModalRestService.createArtifact();
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("Bad Request: error: Le champ I want to (i_want_to) est obligatoire.");
            expect(promise).toBeRejected();
        });

        it("Given the server didn't respond, when I create an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", function() {
            mockBackend.expectPOST('/api/v1/artifacts').respond(404, undefined, undefined, 'Not Found');

            var promise = TuleapArtifactModalRestService.createArtifact();
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("404 Not Found");
            expect(promise).toBeRejected();
        });
    });

    describe("getFollowupsComments() -", function() {
        it("Given an artifact id, a limit, an offset and an order, when I get the artifact's followup comments, then a promise will be resolved with an object containing the comments in a 'results' property and the total number of comments in a 'total' property", function() {
            var response = [
                {
                    id: 629,
                    last_comment: {
                        body: "orometer",
                        format: "text"
                    }
                }, {
                    id: 593,
                    last_comment: {
                        body: "mystagogic",
                        format: "html"
                    }
                }
            ];
            mockBackend.expectGET('/api/v1/artifacts/148/changesets?fields=comments&limit=66&offset=23&order=desc').respond(
                    JSON.stringify(response),
                    {
                        'X-PAGINATION-SIZE': 74
                    }
                );

            var promise = TuleapArtifactModalRestService.getFollowupsComments(148, 66, 23, 'desc');
            mockBackend.flush();

            expect(promise).toBeResolved();
            var data = promise.$$state.value;
            expect(data.total).toEqual('74');
            expect(data.results[0]).toEqual(jasmine.objectContaining(response[0]));
            expect(data.results[1]).toEqual(jasmine.objectContaining(response[1]));
        });
    });

    describe("uploadTemporaryFile() -", function() {
        it("Given a file object with a filename, a filetype and a chunks array and given a description, when I upload a new temporary file, then a promise will be resolved with the new temporary file's id", function() {
            var response = { id: 4 };
            mockBackend.expectPOST('/api/v1/artifact_temporary_files', {
                name: "bitterheartedness",
                mimetype: "image/png",
                content: "FwnCeTwZcgBOiH",
                description: "bullboat metrosteresis classicality"
            }).respond(JSON.stringify(response));

            var file_to_upload = {
                filename: "bitterheartedness",
                filetype: "image/png",
                chunks: [
                    "FwnCeTwZcgBOiH"
                ]
            };
            var description = "bullboat metrosteresis classicality";

            var promise = TuleapArtifactModalRestService.uploadTemporaryFile(file_to_upload, description);
            mockBackend.flush();

            expect(promise).toBeResolvedWith(4);
        });
    });

    describe("uploadAdditionalChunk() -", function() {
        it("Given a temporary file id, a chunk and a chunk offset, when I upload an additional chunk to be appended to a temporary file, then a promise will be resolved", function() {
            mockBackend.expectPUT('/api/v1/artifact_temporary_files/9', {
                content: "rmNcNnltd",
                offset: 4
            }).respond();

            var promise = TuleapArtifactModalRestService.uploadAdditionalChunk(9, "rmNcNnltd", 4);
            mockBackend.flush();

            expect(promise).toBeResolved();
        });
    });

    describe("getUserPreference() -", function() {
        it(" Given a key, when I search for a preference, then a promise will be resolved with an object of user preference representation", function() {
            var response = {
                key  : 'tracker_comment_invertorder_93',
                value: '1'
            };
            mockBackend.expectGET('/api/v1/users/102/preferences?key=tracker_comment_invertorder_93').respond(JSON.stringify(response));

            var promise = TuleapArtifactModalRestService.getUserPreference(102, 'tracker_comment_invertorder_93');
            mockBackend.flush();

            expect(promise).toBeResolved();
            var result = promise.$$state.value;
            expect(result).toEqual(jasmine.objectContaining({
                key  : 'tracker_comment_invertorder_93',
                value: '1'
            }));
        });
    });

    describe("editArtifact() -", function() {
        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", function() {
            var followup_comment = {
                value: '',
                format: 'text'
            };
            var field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] }
            ];
            mockBackend.expectPUT('/api/v1/artifacts/8354', {
                values: field_values,
                comment: followup_comment
            }).respond(200);

            var promise = TuleapArtifactModalRestService.editArtifact(8354, field_values, followup_comment);
            mockBackend.flush();

            expect(promise).toBeResolvedWith(jasmine.objectContaining({
                id: 8354
            }));
        });

        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact with a comment, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", function() {
            var followup_comment = {
                value: 'This is <b>my</b> comment',
                format: 'html'
            };
            var field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] }
            ];
            mockBackend.expectPUT('/api/v1/artifacts/8354', {
                values: field_values,
                comment: followup_comment
            }).respond(200);

            var promise = TuleapArtifactModalRestService.editArtifact(8354, field_values, followup_comment);
            mockBackend.flush();

            expect(promise).toBeResolvedWith(jasmine.objectContaining({
                id: 8354
            }));
        });

        it("Given the server didn't respond, when I edit an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", function() {
            mockBackend.expectPUT('/api/v1/artifacts/6144').respond(404, undefined, undefined, 'Not Found');

            var promise = TuleapArtifactModalRestService.editArtifact(6144);
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("404 Not Found");
            expect(promise).toBeRejected();
        });
    });

    describe("getFileUploadRules() -", function() {
        it("When I get the file upload rules, then a promise will be resolved with an object containing the disk quota for the logged user, her disk usage and the max chunk size that can be sent when uploading a file", function() {
            mockBackend.expect('OPTIONS', '/api/v1/artifact_temporary_files').respond({}, {
                'X-QUOTA'                    : '2229535',
                'X-DISK-USAGE'               : '596878',
                'X-UPLOAD-MAX-FILE-CHUNKSIZE': '732798'
            });

            var promise = TuleapArtifactModalRestService.getFileUploadRules();
            mockBackend.flush();

            expect(promise).toBeResolvedWith({
                disk_quota    : 2229535,
                disk_usage    : 596878,
                max_chunk_size: 732798
            });
        });
    });
});
