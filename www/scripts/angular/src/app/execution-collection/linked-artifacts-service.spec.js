import execution_module from './execution-collection.js';
import angular          from 'angular';
import 'angular-mocks';

describe("LinkedArtifactsService -", () => {
    let $q,
        $rootScope,
        LinkedArtifactsService,
        ExecutionRestService,
        SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(execution_module);
        angular.mock.inject(function(
            _$q_,
            _$rootScope_,
            _ExecutionRestService_,
            _LinkedArtifactsService_,
            _SharedPropertiesService_
        ) {
            $q                      = _$q_;
            $rootScope              = _$rootScope_;
            ExecutionRestService    = _ExecutionRestService_;
            LinkedArtifactsService      = _LinkedArtifactsService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        installPromiseMatchers();
    });

    describe("getAllLinkedIssues", () => {
        let execution;
        beforeEach(function() {
            execution = { id: 617 };
        });

        it("Given an execution, then the filtered list of issues linked to it (according to the issue tracker defined in config) will be returned via a progress callback", () => {
            const linked_issue = {
                id: 609,
                tracker: { id: 52 },
                xref: 'bugs #609',
                title: 'faultily Curavecan'
            };

            const linked_artifacts = [
                linked_issue,
                {
                    id: 199,
                    tracker: { id: 27 },
                    xref: 'story #199',
                    title: 'delphine Teutophilism'
                }, {
                    id: 506,
                    tracker: { id: 37 },
                    xref: 'epic #506',
                    title: 'unpromulgated stoma'
                }
            ];
            spyOn(ExecutionRestService, "getLinkedArtifacts").and.returnValue($q.when({collection: linked_artifacts,
                total: 3
            }));
            spyOn(SharedPropertiesService, "getIssueTrackerId").and.returnValue(52);

            LinkedArtifactsService.getAllLinkedIssues(execution, 0, (result) => {
                expect(result).toEqual(linked_artifacts);
            });
        });

        it("Given there are more than 50 linked artifacts, then it will query twice", function() {
            const bug = {
                id: 11,
                tracker: { id: 16 }
            };
            spyOn(ExecutionRestService, "getLinkedArtifacts").and.callFake((
                execution,
                limit,
                offset,
                progress_callback
            ) => {
                if (offset === 0) {
                    return $q.when({
                        collection: Array(50).fill(bug),
                        total: 60
                    });
                }

                return $q.when({
                    collection: Array(10).fill(bug),
                    total: 60
                });
            })

            spyOn(SharedPropertiesService, "getIssueTrackerId").and.returnValue(16);

            let all_issues  = [];
            LinkedArtifactsService.getAllLinkedIssues(execution, 0, (result) => {
                all_issues.push(...result);
            });
            $rootScope.$apply();

            expect(all_issues.length).toBe(60);
            expect(ExecutionRestService.getLinkedArtifacts.calls.count()).toBe(2);
        });

        it("returns the promise from the ExecutionRestService", function() {
            spyOn(ExecutionRestService, "getLinkedArtifacts").and.returnValue($q.when({
                collection: [],
                total: 0
            }));

            const promise = LinkedArtifactsService.getAllLinkedIssues(execution, 0, angular.noop);

            expect(promise).toBeResolved();
        });
    });
});
