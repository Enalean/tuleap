describe("TuleapArtifactModalRestService", function() {
    var mockBackend, $q, $rootScope, TuleapArtifactModalRestService, request, response;
    beforeEach(function() {
        module('tuleap.artifact-modal');

        inject(function(_TuleapArtifactModalRestService_, $httpBackend, _$q_, _$rootScope_) {
            TuleapArtifactModalRestService = _TuleapArtifactModalRestService_;
            mockBackend = $httpBackend;
            $q = _$q_;
            $rootScope = _$rootScope_;
        });

        request = null;
        response = null;
    });

    // Remove all Restangular/AngularJS added methods in order to use Jasmine toEqual between the retrieved resource and the model
    function sanitizeSingle(restangularized) {
        return _.omit(restangularized, "route", "parentResource", "getList", "get", "post", "put", "remove", "head", "trace", "options", "patch",
            "$get", "$save", "$query", "$remove", "$delete", "$put", "$post", "$head", "$trace", "$options", "$patch",
            "$then", "$resolved", "restangularCollection", "customOperation", "customGET", "customPOST",
            "customPUT", "customDELETE", "customGETLIST", "$getList", "$resolved", "restangularCollection", "one", "all", "doGET", "doPOST",
            "doPUT", "doDELETE", "doGETLIST", "addRestangularMethod", "getRestangularUrl", "getRequestedUrl", "clone", "reqParams", "withHttpConfig", "plain",
            "several", "oneUrl", "allUrl", "fromServer", "save", "getParentList");
    }

    function sanitizeRestangular(restangularized) {
        var sanitized;
        if (_.isArray(restangularized)) {
            sanitized = _.map(restangularized, sanitizeSingle);
        } else {
            sanitized = sanitizeSingle(restangularized);
        }
        return sanitized;
    }

    afterEach(function() {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getTrackerStructure() - Given a tracker id, when I get the tracker's structure, then a promise will be resolved with the tracker's structure", function() {
        response = {
            id: 84,
            label: "Functionize recklessly"
        };

        mockBackend.expectGET('/api/v1/trackers/84').respond(JSON.stringify(response));

        var promise = TuleapArtifactModalRestService.getTrackerStructure(84);
        var success = jasmine.createSpy('success');
        promise.then(success);
        mockBackend.flush();

        var expected = sanitizeRestangular(success.calls[0].args[0]);
        expect(expected).toEqual({
            id: 84,
            label: "Functionize recklessly"
        });
    });

    it("getTrackerArtifacts() - Given a tracker containing two artifacts and given its id, when I get the tracker's artifacts, then a promise will be resolved with an array of 2 artifacts", function() {
        response = [
            {
                id: 862,
                values: [
                    {
                        field_id: 80,
                        label: "eucolite",
                        value: "paraleipsis"
                    }, {
                        field_id: 60,
                        label: "kammererite",
                        bind_value_ids: [29, 64]
                    }
                ]
            }, {
                id: 755,
                values: [
                    {
                        field_id: 29,
                        label: "manuscriptural",
                        value: "capronyl"
                    }, {
                        field_id: 64,
                        label: "disgorge",
                        bind_value_ids: [1,92]
                    }
                ]
            }
        ];

        mockBackend.expectGET('/api/v1/trackers/91/artifacts?values=all').respond(JSON.stringify(response));

        var promise = TuleapArtifactModalRestService.getTrackerArtifacts(91);
        var success = jasmine.createSpy("success");
        promise.then(success);
        mockBackend.flush();

        var expected = sanitizeRestangular(success.calls[0].args[0]);
        expect(expected).toEqual(response);
    });

    it("getArtifact() - Given an artifact id, when I get the artifact, then a promise will be resolved with", function() {
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
        var success = jasmine.createSpy("success");
        promise.then(success);
        mockBackend.flush();

        var expected = sanitizeRestangular(success.calls[0].args[0]);
        expect(expected).toEqual(response);
    });

    it("getArtifactsTitles() - Given a tracker containing two artifacts and given its id, when I get the tracker's artifacts titles, then a promise will be resolved with an array of objects containing the artifact's id, label and the value of the artifact's title field", function() {
        var first_deferred = $q.defer();
        var second_deferred = $q.defer();
        spyOn(TuleapArtifactModalRestService, "getTrackerStructure").andReturn(first_deferred.promise);
        spyOn(TuleapArtifactModalRestService, "getTrackerArtifacts").andReturn(second_deferred.promise);
        first_deferred.resolve({
            label: "overlace",
            semantics: {
                title: {
                    field_id: 303
                }
            }
        });
        second_deferred.resolve([
            {
                id: 747,
                values: [
                    {
                        field_id: 850,
                        label: "stockbreeding",
                        value: 91
                    }, {
                        field_id: 303,
                        label: "cafenet",
                        value: "Poeciliidae"
                    }
                ]
            }, {
                id: 765,
                values: [
                    {
                        field_id: 850,
                        label: "stockbreeding",
                        value: 479
                    }, {
                        field_id: 303,
                        label: "cafenet",
                        value: "laminated"
                    }
                ]
            }
        ]);

        var promise = TuleapArtifactModalRestService.getArtifactsTitles(58);
        var success = jasmine.createSpy("success");
        promise.then(success);
        $rootScope.$apply();

        expect(success).toHaveBeenCalledWith([
            { id: 747, title: "overlace #747 - Poeciliidae" },
            { id: 765, title: "overlace #765 - laminated" }
        ]);
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
            var success = jasmine.createSpy('success');
            promise.then(success);
            mockBackend.flush();

            var expected = sanitizeRestangular(success.calls[0].args[0]);
            expect(expected).toEqual({
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
            var failure = jasmine.createSpy("failure");
            promise.then(null, failure);
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("Bad Request: error: Le champ I want to (i_want_to) est obligatoire.");
            expect(failure).toHaveBeenCalled();
        });

        it("Given the server didn't respond, when I create an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", function() {
            mockBackend.expectPOST('/api/v1/artifacts').respond(404, undefined, undefined, 'Not Found');

            var promise = TuleapArtifactModalRestService.createArtifact();
            var failure = jasmine.createSpy("failure");
            promise.then(null, failure);
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("404 Not Found");
            expect(failure).toHaveBeenCalled();
        });
    });

    describe("editArtifact() -", function() {
        it("Given an artifact id and an array of fields containing their id and selected value, when I edit an artifact, then the field values will be sent using the edit REST route and a promise will be resolved with the edited artifact's id", function() {
            var field_values = [
                { field_id: 47, value: "unpensionableness" },
                { field_id: 71, bind_value_ids: [726, 332] }
            ];
            mockBackend.expectPUT('/api/v1/artifacts/8354', {
                values: field_values
            }).respond(200);

            var promise = TuleapArtifactModalRestService.editArtifact(8354, field_values);
            var success = jasmine.createSpy("success");
            promise.then(success);
            mockBackend.flush();

            var expected = sanitizeRestangular(success.calls[0].args[0]);
            expect(expected).toEqual({
                id: 8354
            });
        });

        it("Given the server didn't respond, when I edit an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", function() {
            mockBackend.expectPUT('/api/v1/artifacts/6144').respond(404, undefined, undefined, 'Not Found');

            var promise = TuleapArtifactModalRestService.editArtifact(6144);
            var failure = jasmine.createSpy("failure");
            promise.then(null, failure);
            mockBackend.flush();

            expect(TuleapArtifactModalRestService.error.is_error).toBeTruthy();
            expect(TuleapArtifactModalRestService.error.error_message).toEqual("404 Not Found");
            expect(failure).toHaveBeenCalled();
        });
    });
});
