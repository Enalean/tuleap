import angular from "angular";
import { Fault } from "@tuleap/fault";
import tuleap_pullrequest_module from "../../app.js";
import pullrequest_summary_controller from "./pull-request-summary-controller.js";

import "angular-mocks";

describe("PullRequestSummaryController -", () => {
    let $q,
        $rootScope,
        $state,
        PullRequestSummaryController,
        UserRestService,
        PullRequestService,
        ErrorModalService;

    beforeEach(() => {
        let $controller;

        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function (
            _$controller_,
            _$q_,
            _$rootScope_,
            _UserRestService_,
            _PullRequestService_,
            _ErrorModalService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            UserRestService = _UserRestService_;
            PullRequestService = _PullRequestService_;
            ErrorModalService = _ErrorModalService_;
        });

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
            }
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

        it("onFetchErrorCallback() should open the error modal to display the provided fault", () => {
            const showErrorMessage = jest.spyOn(ErrorModalService, "showErrorMessage");
            const tuleap_api_fault = Fault.fromMessage("Forbidden");

            PullRequestSummaryController.onFetchErrorCallback(
                new CustomEvent("fetch-error", { detail: { fault: tuleap_api_fault } })
            );

            expect(showErrorMessage).toHaveBeenCalledWith(tuleap_api_fault);
        });
    });
});
