import trafficlights_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe ('ExecutionService - ', function () {
    var $q,
        $rootScope,
        ExecutionRestService,
        ExecutionService;

    beforeEach(function() {
        angular.mock.module(trafficlights_module);

        angular.mock.inject(function(
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

            var executions_by_categories_by_campaigns_results = {
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

            var get_remote_executions_request = $q.defer();
            var get_executions_request = $q.defer();
            spyOn(ExecutionService, "getAllRemoteExecutions").and.returnValue(get_remote_executions_request.promise);
            spyOn(ExecutionRestService, 'getRemoteExecutions').and.returnValue(get_executions_request.promise);

            var promise = ExecutionService.loadExecutions(campaign.id);
            get_executions_request.resolve(response);
            get_remote_executions_request.resolve(response.results);

            promise.then(function() {
                expect(ExecutionService.categories).toEqual(categories_results);
                expect(ExecutionService.executions).toEqual(execution_results);
                expect(ExecutionService.executions_by_categories_by_campaigns).toEqual(executions_by_categories_by_campaigns_results);
            });

            $rootScope.$apply();
        });
    });

    describe('getAllRemoteExecutions() -', function() {
        it('Given that I have more remote executions than the given fetching limit, when I get all remote executions, then all the remote executions are fetched', function() {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                uri: "trafficlights_campaigns/6"
            };

            var remote_executions_count = 2;

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
                total: remote_executions_count
            };

            var get_remote_executions_request = $q.defer();
            spyOn(ExecutionRestService, 'getRemoteExecutions').and.returnValue(get_remote_executions_request.promise);

            var promise = ExecutionService.getAllRemoteExecutions(campaign.id, 1, 0);
            get_remote_executions_request.resolve(response);

            promise.then(function() {
                expect(ExecutionService.executions.count).toEqual(remote_executions_count);
            });
        });
    });

    describe('synchronizeExecutions() -', function() {
        var campaign_id = 6,
            execution_1 = { id: 1, definition: { category: 'Security' } },
            execution_2 = { id: 2, definition: { category: 'NonRegression' } },
            service_executions = null,
            service_categories = null,
            get_remote_executions = null,
            get_all_remote_executions = null;

        var resolveExecutions = function(executions) {
            var data = executions || [];

            get_remote_executions.resolve({
                total: data.length,
                results: data
            });
            get_all_remote_executions.resolve(data);
        };

        beforeEach(function() {
            ExecutionService.campaign_id = campaign_id;
            ExecutionService.executions = {
                1: execution_1,
                2: execution_2
            };
            ExecutionService.executions_by_categories_by_campaigns[campaign_id] = {
                Security: {
                    label: 'Security',
                    executions: [execution_1]
                },
                NonRegression: {
                    label: 'NonRegression',
                    executions: [execution_2]
                }
            };

            service_executions = function() {
                return ExecutionService.executions;
            };

            service_categories = function() {
                return ExecutionService.executions_by_categories_by_campaigns[campaign_id];
            };

            get_remote_executions = $q.defer();
            spyOn(ExecutionRestService, 'getRemoteExecutions').and.returnValue(get_remote_executions.promise);

            get_all_remote_executions = $q.defer();
            spyOn(ExecutionService, 'getAllRemoteExecutions').and.returnValue(get_all_remote_executions.promise);
        });

        it('Given that I have different sets of loaded and remote executions, when I synchronize them, then the executions not present remotely are unloaded', function() {
            var remote_executions = [execution_1];

            ExecutionService.synchronizeExecutions(campaign_id).then(function() {
                expect(service_executions()[2]).toBeUndefined();
                expect(service_categories().NonRegression.executions.length).toEqual(0);
            });

            resolveExecutions(remote_executions);

            $rootScope.$apply();
        });

        it('Given that I have different sets of loaded and remote executions, when I synchronize them, then the executions not present locally are loaded', function() {
            var remote_executions = [execution_1, execution_2];

            ExecutionService.executions = { 1: execution_1 };
            ExecutionService.executions_by_categories_by_campaigns[campaign_id] = {
                Security: { label: 'Security', executions: [execution_1] }
            };

            ExecutionService.synchronizeExecutions(campaign_id).then(function() {
                expect(service_executions()[2]).toEqual(execution_2);
                expect(service_categories().NonRegression.executions.length).toEqual(1);
            });

            resolveExecutions(remote_executions);

            $rootScope.$apply();
        });

        it('Given that I have the same sets of loaded and remote executions, when I synchronize them, then the local executions are not duplicated', function() {
            var remote_executions = [execution_1, execution_2];

            ExecutionService.synchronizeExecutions(campaign_id).then(function() {
                expect(_.size(service_executions())).toEqual(2);
                expect(_.size(service_categories().Security.executions)).toEqual(1);
                expect(_.size(service_categories().NonRegression.executions)).toEqual(1);
            });

            resolveExecutions(remote_executions);

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

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn"
                }
            };

            var executions_by_categories_by_campaigns = {
                6: categories
            };

            ExecutionService.campaign_id                           = 6;
            ExecutionService.campaign                              = campaign;
            ExecutionService.categories                            = categories;
            ExecutionService.executions_by_categories_by_campaigns = executions_by_categories_by_campaigns;
            ExecutionService.addTestExecution(execution);
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

    describe("removeTestExecution() -", function() {
        it("Given that campaign, when I remove an execution, then it's removed from executions and categories and campaign numbers are updated", function () {
            var campaign = {
                id: "6",
                label: "Release 1",
                status: "Open",
                nb_of_passed: 0,
                nb_of_failed: 0,
                nb_of_notrun: 1,
                nb_of_blocked: 0
            };

            var execution = {
                id: 4,
                status: "notrun",
                definition: {
                    category: "Svn"
                }
            };

            var categories = {
                Svn: {
                    label: "Svn",
                    executions: [execution]
                }
            };

            var executions_by_categories_by_campaigns = {
                6: categories
            };

            ExecutionService.campaign_id                           = 6;
            ExecutionService.campaign                              = campaign;
            ExecutionService.categories                            = categories;
            ExecutionService.executions_by_categories_by_campaigns = executions_by_categories_by_campaigns;
            ExecutionService.executions = { 4: execution };

            ExecutionService.removeTestExecution(execution);

            expect(ExecutionService.executions[4]).toEqual(undefined);
            expect(ExecutionService.executions_by_categories_by_campaigns[6].Svn.executions[4]).toEqual(undefined);
            expect(ExecutionService.campaign.nb_of_notrun).toEqual(0);
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
