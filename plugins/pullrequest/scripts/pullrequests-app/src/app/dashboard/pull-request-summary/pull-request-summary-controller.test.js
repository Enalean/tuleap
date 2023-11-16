import angular from "angular";
import tuleap_pullrequest_module from "../../app.js";
import pullrequest_summary_controller from "./pull-request-summary-controller.js";

import "angular-mocks";

describe("PullRequestSummaryController -", () => {
    let $q, $rootScope, $state, PullRequestSummaryController, UserRestService, PullRequestService;

    beforeEach(() => {
        let $controller;

        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(
            function (_$controller_, _$q_, _$rootScope_, _UserRestService_, _PullRequestService_) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                UserRestService = _UserRestService_;
                PullRequestService = _PullRequestService_;
            },
        );

        $state = {
            go: () => {},
        };
        jest.spyOn(UserRestService, "getUser").mockReturnValue($q.when());

        PullRequestSummaryController = $controller(
            pullrequest_summary_controller,
            {
                $state,
                PullRequestService,
            },
            {
                pull_request: {
                    user_id: 134,
                },
            },
        );
    });

    describe("init()", () => {
        it("when I create the controller, then it will fetch the pull request's author using the REST service", () => {
            const user_id = 112;
            const user = {
                id: 112,
                display_name: "Oliver Haglund",
            };

            UserRestService.getUser.mockReturnValue($q.when(user));
            PullRequestSummaryController.pull_request = {
                user_id: user_id,
            };

            PullRequestSummaryController.$onInit();
            $rootScope.$apply();

            expect(UserRestService.getUser).toHaveBeenCalledWith(user_id);
            expect(PullRequestSummaryController.author).toBe(user);
        });
    });
});
