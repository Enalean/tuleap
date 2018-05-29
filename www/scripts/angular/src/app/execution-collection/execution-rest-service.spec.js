import execution_module from './execution-collection.js';
import angular          from 'angular';
import 'angular-mocks';

describe('ExecutionRestService - ', () => {
    let mockBackend, ExecutionRestService, SharedPropertiesService;
    const UUID = '123';

    beforeEach(() => {
        angular.mock.module(execution_module);

        angular.mock.inject(function(
            $httpBackend,
            _ExecutionRestService_,
            _SharedPropertiesService_
        ) {
            mockBackend             = $httpBackend;
            ExecutionRestService    = _ExecutionRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, 'getUUID').and.returnValue(UUID);
    });

    afterEach(() => {
        mockBackend.verifyNoOutstandingExpectation();
        mockBackend.verifyNoOutstandingRequest();
    });

    it("getRemoteExecutions() - ", () => {
        const response = [
            {
                id: 4
            }, {
                id: 2
            }
        ];

        mockBackend
            .expectGET('/api/v1/testmanagement_campaigns/1/testmanagement_executions?limit=10&offset=0')
            .respond(JSON.stringify(response));

        const promise = ExecutionRestService.getRemoteExecutions(1, 10, 0);
        mockBackend.flush();

        promise.then(executions => {
            expect(executions.results.length).toEqual(2);
        });
    });

    it("postTestExecution() - ", () => {
        const execution = {
            id: 4,
            status: "notrun"
        };

        mockBackend
            .expectPOST('/api/v1/testmanagement_executions')
            .respond(execution);

        const promise = ExecutionRestService.postTestExecution("notrun", "CentOS 5 - PHP 5.1");

        mockBackend.flush();

        promise.then(execution_updated => {
            expect(execution_updated.id).toBeDefined();
        });
    });

    it("putTestExecution() - ", () => {
        const execution = {
            id: 4,
            status: "passed",
            previous_result: {
                result: "",
                status: "notrun"
            }
        };

        mockBackend
            .expectPUT('/api/v1/testmanagement_executions/4?results=nothing&status=passed&time=1')
            .respond(execution);

        const promise = ExecutionRestService.putTestExecution(4, 'passed', 1, 'nothing');

        mockBackend.flush();

        promise.then(execution_updated => {
            expect(execution_updated.id).toBeDefined();
        });
    });

    it("changePresenceOnTestExecution() - ", () => {
        mockBackend
            .expectPATCH('/api/v1/testmanagement_executions/9/presences')
            .respond();

        const promise = ExecutionRestService.changePresenceOnTestExecution(9, 4);

        mockBackend.flush();

        promise.then(response => {
            expect(response.status).toEqual(200);
        });
    });

    it("linkIssue() - ", () => {
        const issueId   = 400;
        const execution = {
            id: 100,
            previous_result: {
                result: 'Something wrong'
            },
            definition: {
                summary: 'test summary',
                description: 'test description'
            }
        };

        const expectedBody = new RegExp(execution.definition.summary
                                    + ".*"
                                    + execution.definition.description);
        const matchPayload = {
            id: issueId,
            comment: {
                body  : 'MATCHING TEST SUMMARY + DESCRIPTION',
                format: 'html'
            },
            test: function(data) {
                const payload = JSON.parse(data);
                return payload.issue_id === issueId &&
                    expectedBody.test(payload.comment.body) &&
                    payload.comment.format === 'html';
            }
        };
        mockBackend
            .expectPATCH('/api/v1/testmanagement_executions/100/issues', matchPayload)
            .respond();

        const promise = ExecutionRestService.linkIssue(issueId, execution);

        mockBackend.flush();

        promise.then(response => {
            expect(response.status).toEqual(200);
        });
    });

    it("getLinkedArtifacts() - ", () => {
        const linked_issues = [
            {
                id: 219,
                xref: 'bug #219',
                title: 'mascleless dollhouse',
                tracker: { id: 23 }
            }, {
                id: 402,
                xref: 'bug #402',
                title: 'sugar candescent',
                tracker: { id: 23 }
            }
        ];

        mockBackend
            .expectGET('/api/v1/artifacts/148/linked_artifacts?direction=forward&limit=10&nature=&offset=0')
            .respond(angular.toJson({
                collection: linked_issues
            }), {
                'X-Pagination-Size': 2
            });

        const test_execution = { id: 148 };
        const promise = ExecutionRestService.getLinkedArtifacts(test_execution, 10, 0);
        mockBackend.flush();

        promise.then(result => {
            expect(result).toEqual({
                collection: linked_issues,
                total: 2
            });
        });
    });

    it("getArtifactById() -", () => {
        const artifact = {
            id: 61,
            xref: 'bug #61',
            title: 'intercloud haustorium',
            tracker: { id: 4 }
        };
        mockBackend
            .expectGET('/api/v1/artifacts/61')
            .respond(angular.toJson(artifact));

        const promise = ExecutionRestService.getArtifactById(61);
        mockBackend.flush();

        promise.then(result => {
            expect(result).toEqual(artifact);
        });
    });

    describe('updateStepStatus()', () => {
        it('Given an execution id, a step id and a status, then the REST route will be called', () => {
            const test_execution = { id: 26 };
            const step_id = 96;
            const status = 'failed';
            mockBackend
                .expectPATCH(
                    '/api/v1/testmanagement_executions/26',
                    {
                        steps_results: [{ step_id, status }]
                    },
                    headers => headers['X-Client-UUID'] === UUID
                )
                .respond(200);

            const promise = ExecutionRestService.updateStepStatus(test_execution, step_id, status);
            mockBackend.flush();

            promise.catch(() => fail());
        });

        it('Given there is a REST error, then a promise will be rejected with the error message', done => {
            const test_execution = { id: 21 };
            const step_id = 38;
            const status = 'blocked';
            mockBackend.whenPATCH('/api/v1/testmanagement_executions/21').respond(403, {
                error: { message: 'This user cannot update the execution' }
            });

            const promise = ExecutionRestService.updateStepStatus(test_execution, step_id, status);

            promise.then(
                () => fail(),
                error => {
                    expect(error).toEqual('This user cannot update the execution');
                    done();
                }
            );
            mockBackend.flush();
        });
    });
});
