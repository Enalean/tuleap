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

    describe("addTestExecution() -", function() {
        it("Given that campaign, when I add an execution, then it's added with values and campaign with correct numbers", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 0
            };

            var categories = {};

            var executions = [
                {
                    id: 4,
                    status: "notrun",
                    definition: {
                        category: "Svn"
                    }
                }
            ];

            var executions_by_categories_by_campaigns = {
                6: categories
            };

            ExecutionService.campaign_id                           = 6;
            ExecutionService.campaign                              = campaign;
            ExecutionService.categories                            = categories;
            ExecutionService.executions_by_categories_by_campaigns = executions_by_categories_by_campaigns;
            ExecutionService.addTestExecutions(executions);
            expect(ExecutionService.executions[4]).toEqual({
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn"
                }
            });
            expect(ExecutionService.campaign).toEqual({
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0
            });
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
                nb_of_notrun: 1,
                nb_of_blocked: 0
            };

            var execution_to_save = {
                id: 4,
                status: "failed"
            };

            var executions = {
                4: {
                    id: 4,
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
                nb_of_notrun: 0,
                nb_of_blocked: 0
            };

            ExecutionService.campaign   = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save);

            expect(ExecutionService.executions[4].status).toEqual("failed");
            expect(ExecutionService.campaign).toEqual(campaign_results);
        });

        it("Given that campaign, when I update an execution with different values, then the execution and the campaign must change", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 0,
                nb_of_blocked: 1
            };

            var execution_to_save = {
                id: 4,
                status: "notrun"
            };

            var executions = {
                4: {
                    id: 4,
                    previous_result: {
                        status: "blocked"
                    }
                }
            };

            var campaign_copy = _.clone(campaign);

            ExecutionService.campaign   = campaign;
            ExecutionService.executions = executions;
            ExecutionService.updateTestExecution(execution_to_save);

            expect(ExecutionService.campaign).not.toEqual(campaign_copy);
            expect(Object.keys(ExecutionService.campaign).length).toEqual(Object.keys(campaign_copy).length);
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
                avatar_url: 'url',
                score: 0
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
                avatar_url: 'url',
                uuid: '456',
                score: 0
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url',
                uuid: '123',
                score: 0
            };

            var executions = {
                4: {
                    id: 4,
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

        it("Given that executions with user_one on, when I user_one views a test, then there is twice user_one on but once on campaign", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                uuid: '456'
            };

            var user_one_bis = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                uuid: '123'
            };

            var executions = {
                4: {
                    id: 4,
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
                user_one, user_one_bis
            ];

            ExecutionService.executions = executions;
            ExecutionService.viewTestExecution(4, user_one_bis);

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

            var results                      = [user_one];
            var result_presences_on_campaign = [user_one, user_two];

            ExecutionService.executions = executions;
            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removeViewTestExecutionByUUID('456');

            expect(ExecutionService.executions[4].viewed_by).toEqual(results);
            expect(ExecutionService.executions[5].viewed_by).toEqual(results);
            expect(ExecutionService.presences_on_campaign).toEqual(result_presences_on_campaign);
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

    describe("addPresenceCampaign() -", function() {
        it("Given that presences on campaign, when I add user_two with a score, then user_two is on campaign", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                score: 0
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url',
                score: 0
            };

            var presences_on_campaign = [user_one];

            var results = [user_one, user_two];

            ExecutionService.presences_on_campaign = presences_on_campaign;
            ExecutionService.addPresenceCampaign(user_two);

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });

        it("Given that presences on campaign, when I add user_two with no score, then user_two is on campaign with score 0", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                score: 0
            };

            var user_two = {
                id: 102,
                real_name: 'Test',
                avatar_url: 'url'

            };

            var presences_on_campaign = [user_one];

            var results = [user_one, user_two];

            ExecutionService.presences_on_campaign = presences_on_campaign;
            ExecutionService.addPresenceCampaign(user_two);

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });
    });

    describe("removePresenceCampaign() -", function() {
        it("Given that executions with user_two on, when I remove user_one from campaign, then there is only user_two on campaign", function () {
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
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_two]
                }
            };

            var results = [user_two];

            ExecutionService.executions            = executions;
            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removePresenceCampaign(user_one);

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });

        it("Given that executions with user_one and user_two on, when I remove user_one from campaign, then they stay on campaign", function () {
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
                5: {
                    id: 5,
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

            var results = [user_one, user_two];

            ExecutionService.executions            = executions;
            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removePresenceCampaign(user_one);

            expect(ExecutionService.presences_on_campaign).toEqual(results);
        });
    });

    describe("removeAllPresencesOnCampaign() -", function() {
        it("Given that executions with user_two on, when I remove all from campaign, then there is nobody on campaign", function () {
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

            ExecutionService.presences_on_campaign = [user_one, user_two];
            ExecutionService.removeAllPresencesOnCampaign(user_one);

            expect(ExecutionService.presences_on_campaign).toEqual([]);
        });
    });

    describe("updatePresenceOnCampaign() -", function() {
        it("Given that executions with user_one on, when I update user_one on campaign, then the score is updated", function () {
            var user_one = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                score: 0
            };

            var user_one_updated = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                score: 1
            };

            var executions = {
                5: {
                    id: 5,
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

            var user_one_result = {
                id: 101,
                real_name: 'Test',
                avatar_url: 'url',
                score: 1
            };

            ExecutionService.executions            = executions;
            ExecutionService.presences_on_campaign = [user_one];
            ExecutionService.updatePresenceOnCampaign(user_one_updated);

            expect(ExecutionService.presences_on_campaign[0]).toEqual(user_one_result);
        });
    });

    describe("displayPresencesForAllExecutions() -", function() {
        it("Given that executions, when I display all users, then there users are on the associate execution", function () {
            var user_one = {
                id: '101',
                real_name: 'name',
                avatar_url: 'avatar',
                uuid: '1234'
            };

            var user_two = {
                id: '102',
                real_name: 'name',
                avatar_url: 'avatar',
                uuid: '4567'
            };

            var presences = {
                4: [
                    {
                        id: '101',
                        real_name: 'name',
                        avatar_url: 'avatar',
                        uuid: '1234'
                    }
                ],
                5: [
                    {
                        id: '102',
                        real_name: 'name',
                        avatar_url: 'avatar',
                        uuid: '4567'
                    }
                ]
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            };

            var results = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_one]
                },
                5: {
                    id: 5,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    },
                    viewed_by: [user_two]
                }
            };

            ExecutionService.executions             = executions;
            ExecutionService.executions_loaded      = true;
            ExecutionService.presences_loaded       = true;
            ExecutionService.presences_by_execution = presences;
            ExecutionService.displayPresencesForAllExecutions();

            expect(ExecutionService.executions).toEqual(results);
        });
    });

    describe("displayPresencesByExecution() -", function() {
        it("Given that executions, when I display all users on one execution, then there users are on", function () {
            var user_one = {
                id: '101',
                real_name: 'name',
                avatar_url: 'avatar',
                uuid: '1234'
            };

            var user_two = {
                id: '102',
                real_name: 'name',
                avatar_url: 'avatar',
                uuid: '4567'
            };

            var executions = {
                4: {
                    id: 4,
                    definition: {
                        category: "Svn",
                        description: "test",
                        id: 3,
                        summary: "My first test",
                        uri: "trafficlights_definitions/3"
                    }
                }
            };

            var presences = [user_one, user_two];

            var results = {
                4: {
                    id: 4,
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

            ExecutionService.executions = executions;
            ExecutionService.displayPresencesByExecution(4, presences);

            expect(ExecutionService.executions).toEqual(results);
        });
    });
});
