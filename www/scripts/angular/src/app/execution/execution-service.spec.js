describe ('ExecutionService - ', function () {
    var $q,
        $rootScope,
        ExecutionRestService,
        ExecutionService;

    beforeEach(function() {
        module('campaign');

        inject(function(
            _$q_,
            _$rootScope_,
            _ExecutionRestService_,
            _ExecutionService_
        ) {
            $q                   = _$q_;
            $rootScope           = _$rootScope_;
            ExecutionRestService = _ExecutionRestService_;
            ExecutionService     = _ExecutionService_;
        });

        var executions = {
            results: [
                {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            ],
            total: "1"
        };

        spyOn(ExecutionService, "getExecutions").and.returnValue(executions);
    });

    describe("loadExecutions() -", function() {
        it("Given that campaign, when I load executions, then executions are returned sorted by categories", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "trafficlights_campaigns/6"
            };

            var categories_results = {
                Svn: {
                    label: "Svn",
                    executions: [
                        {
                            id: 4,
                            environment: "CentOS 5 - PHP 5.1",
                            definition: {
                                category: "Svn",
                                description: "test",
                                id: 3,
                                summary: "My first test",
                                uri: "trafficlights_definitions/3"
                            }
                        }
                    ]
                }
            };

            var execution_results = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            };

            var executions_by_categories_by_campaings_results = {
                6: categories_results
            };

            var response = {
                results: [
                    {
                        id: 4,
                        environment: "CentOS 5 - PHP 5.1",
                        definition: {
                            category: "Svn",
                            description: "test",
                            id: 3,
                            summary: "My first test",
                            uri: "trafficlights_definitions/3"
                        }
                    }
                ],
                total: 1
            };

            var get_executions_request = $q.defer();
            spyOn(ExecutionRestService, 'getRemoteExecutions').and.returnValue(get_executions_request.promise);

            var promise = ExecutionService.loadExecutions(campaign.id);
            get_executions_request.resolve(response);

            promise.then(function() {
                expect(ExecutionService.categories).toEqual(categories_results);
                expect(ExecutionService.executions).toEqual(execution_results);
                expect(ExecutionService.executions_by_categories_by_campaigns).toEqual(executions_by_categories_by_campaings_results);
            });

            $rootScope.$apply();
        });
    });

    describe("getExecutionsByDefinitionId() -", function() {
        it("Given that categories, when I get executions by definition id, then only execution with definition id selected are returned", function () {
            var categories = {
                Svn: {
                    executions: [
                        {
                            id: 4,
                            environment: "CentOS 5 - PHP 5.1",
                            definition: {
                                category: "Svn",
                                description: "test",
                                id: 3,
                                summary: "My first test",
                                uri: "trafficlights_definitions/3"
                            }
                        }
                    ],
                    label: "Svn"
                }
            };

            var executions_results = [
                {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            ];

            ExecutionService.categories = categories;
            expect(ExecutionService.getExecutionsByDefinitionId(3)).toEqual(executions_results);
        });
    });

    describe("updateTestExecution() -", function() {
        it("Given that campaign, when I update an execution, then it's updated with new values and campaign with correct numbers", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_not_run: 1,
                nb_of_blocked: 0
            };

            var execution_to_save = {
                id: 4,
                environment: "CentOS 5 - PHP 5.1",
                status: "failed"
            };

            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    status: "notrun",
                    previous_result: {
                        status: "notrun"
                    }
                }
            };

            var campaign_results = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 1,
                nb_of_not_run: 0,
                nb_of_blocked: 0
            };

            ExecutionService.campaign   = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save);
            expect(ExecutionService.executions[4].status).toEqual("failed");
            expect(ExecutionService.campaign).toEqual(campaign_results);
        });
    });

    describe("updateCampaign() -", function() {
        it("Given that campaign, when I update it, then it's updated with new values", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "trafficlights_campaigns/6"
            };

            var campaign_updated = {
                id: "5",
                label: "Release 2",
                status: "Open",
                uri: "trafficlights_campaigns/6"
            };

            ExecutionService.campaign = campaign;
            ExecutionService.updateCampaign(campaign_updated);
            expect(angular.equals(ExecutionService.campaign, campaign_updated)).toBe(true);
        });
    });

    describe("viewTestExecution() -", function() {
        it("Given that executions with no users on, when I user views a test, then there is user on", function () {
            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            };

            var user = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var results = [
                user
            ];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user);
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });

        it("Given that executions with user_one on, when I user_two views a test, then there is user_one and user_two on", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one]
                }
            };

            var results = [
                user_one, user_two
            ];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user_two);
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });
    });

    describe("removeViewTestExecution() -", function() {
        it("Given that executions with two users on, when I remove view of user_one, then there is only user_two on", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one, user_two]
                }
            };

            var results = [user_two];

            ExecutionService.executions = executions;
            ExecutionService.removeViewTestExecution(4, user_one);
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
        });
    });

    describe("removeViewTestExecutionByUUID() -", function() {
        it("Given that executions with two users on, when I remove by user uuid, then the corresponding user is removed", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                uuid: '123'
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url',
                uuid: '456'
            };

            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one, user_two]
                },
                5: {
                    id: 5,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one, user_two]
                }
            };

            var results = [user_one];

            ExecutionService.executions = executions;
            ExecutionService.removeViewTestExecutionByUUID('456');
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
            expect(ExecutionService.executions[5].viewed_by).toEqual(results);
        });
    });

    describe("removeAllViewTestExecution() -", function() {
        it("Given that executions with two users on, when I remove all views, then there is nobody on executions", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url'
            };

            var executions = {
                4: {
                    id: 4,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one, user_two]
                },
                5: {
                    id: 5,
                    environment: "CentOS 5 - PHP 5.1",
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one, user_two]
                }
            };

            var results = [];

            ExecutionService.executions = executions;
            ExecutionService.removeAllViewTestExecution();
            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
            expect(ExecutionService.executions[5].viewed_by).toEqual(results);
        });
    });
});