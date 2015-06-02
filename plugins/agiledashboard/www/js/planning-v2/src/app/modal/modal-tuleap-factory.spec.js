describe("ModalTuleapFactory", function() {
    var mockBackend, $q, $rootScope, ModalTuleapFactory, request, response;
    beforeEach(function() {
        module('modal');

        inject(function (_ModalTuleapFactory_, $httpBackend, _$q_, _$rootScope_) {
            ModalTuleapFactory = _ModalTuleapFactory_;
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

        var promise = ModalTuleapFactory.getTrackerStructure(84);
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

        var promise = ModalTuleapFactory.getTrackerArtifacts(91);
        var success = jasmine.createSpy("success");
        promise.then(success);
        mockBackend.flush();

        var expected = sanitizeRestangular(success.calls[0].args[0]);
        expect(expected).toEqual(response);
    });

    it("getArtifactsTitles() - Given a tracker containing two artifacts and given its id, when I get the tracker's artifacts titles, then a promise will be resolved with an array of objects containing the artifact's id, label and the value of the artifact's title field", function() {
        var first_deferred = $q.defer();
        var second_deferred = $q.defer();
        spyOn(ModalTuleapFactory, "getTrackerStructure").andReturn(first_deferred.promise);
        spyOn(ModalTuleapFactory, "getTrackerArtifacts").andReturn(second_deferred.promise);
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

        var promise = ModalTuleapFactory.getArtifactsTitles(58);
        var success = jasmine.createSpy("success");
        promise.then(success);
        $rootScope.$apply();

        expect(success).toHaveBeenCalledWith([
            { id: 747, title: "overlace #747 - Poeciliidae" },
            { id: 765, title: "overlace #765 - laminated" }
        ]);
    });

    describe("createArtifact() -", function() {
        it("Given a tracker id and an array of fields containing their id and selected values, when I create an artifact, then the field values will be sent using the artifact creation REST route and a promise will be resolved using the new artifact's id", function() {
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

            var promise = ModalTuleapFactory.createArtifact(3, field_values);
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

            var promise = ModalTuleapFactory.createArtifact();
            var failure = jasmine.createSpy("failure");
            promise.then(null, failure);
            mockBackend.flush();

            expect(ModalTuleapFactory.error.is_error).toBeTruthy();
            expect(ModalTuleapFactory.error.error_message).toEqual("400 Bad Request: error: Le champ I want to (i_want_to) est obligatoire.");
            expect(failure).toHaveBeenCalled();
        });

        it("Given the server doesn't respond, when I create an artifact, then the service's error will be set with the HTTP error code and message and a promise will be rejected", function() {
            mockBackend.expectPOST('/api/v1/artifacts').respond(404, undefined, undefined, 'Not Found');

            var promise = ModalTuleapFactory.createArtifact();
            var failure = jasmine.createSpy("failure");
            promise.then(null, failure);
            mockBackend.flush();

            expect(ModalTuleapFactory.error.is_error).toBeTruthy();
            expect(ModalTuleapFactory.error.error_message).toEqual("404 Not Found");
            expect(failure).toHaveBeenCalled();
        });
    });

    it('reorderFieldsInGoodOrder', function() {
        var response = {
            fields: [
                { field_id: 1, type: 'int' },
                { field_id: 2, type: 'int' },
                { field_id: 3, type: 'fieldset' },
                { field_id: 4, type: 'int' },
                { field_id: 5, type: 'column' },
                { field_id: 6, type: 'int' },
                { field_id: 7, type: 'aid' },
                { field_id: 8, type: 'atid' },
                { field_id: 9, type: 'lud' },
                { field_id: 10, type: 'burndown' },
                { field_id: 11, type: 'priority' },
                { field_id: 12, type: 'subby' },
                { field_id: 13, type: 'subon' },
                { field_id: 14, type: 'computed' },
                { field_id: 15, type: 'cross' },
                { field_id: 16, type: 'file' },
                { field_id: 17, type: 'tbl' },
                { field_id: 18, type: 'perm' }
            ],
            structure: [
                { id: 1, content: null },
                { id: 2, content: null },
                { id: 3, content: [
                    { id: 4, content: null },
                    { id: 5, content: [
                        { id: 6, content: null }
                    ]}
                ]},
                { id: 7, content: null },
                { id: 8, content: null },
                { id: 9, content: null },
                { id: 10, content: null },
                { id: 11, content: null },
                { id: 12, content: null },
                { id: 13, content: null },
                { id: 14, content: null },
                { id: 15, content: null },
                { id: 16, content: null },
                { id: 17, content: null },
                { id: 18, content: null }
            ]
        };

        expect(ModalTuleapFactory.reorderFieldsInGoodOrder(response)).toEqual([
            {
                field_id: 1,
                type: 'int',
                template_url: 'field-int.tpl.html'
            },
            {
                field_id: 2,
                type: 'int',
                template_url: 'field-int.tpl.html'
            },
            {
                field_id: 3,
                type: 'fieldset',
                template_url: 'field-fieldset.tpl.html',
                content: [
                    {
                        field_id: 4,
                        type: 'int',
                        template_url: 'field-int.tpl.html'
                    },
                    {
                        field_id: 5,
                        type: 'column',
                        template_url: 'field-column.tpl.html',
                        content: [
                            {
                                field_id: 6,
                                type: 'int',
                                template_url: 'field-int.tpl.html'
                            }
                        ]
                    }
                ]
            }
        ]);
    });
});
