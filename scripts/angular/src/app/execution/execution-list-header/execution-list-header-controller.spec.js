import execution_module from "../execution.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./execution-list-header-controller.js";

import {
    rewire$setSuccess,
    rewire$setError,
    rewire$resetError,
    restore
} from "../../feedback-state.js";

describe("ExecutionListHeaderController -", () => {
    let ExecutionListHeaderController,
        $rootScope,
        $q,
        CampaignService,
        ExecutionService,
        setSuccess,
        setError,
        resetError;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller;

        angular.mock.inject(function(
            _$controller_,
            _CampaignService_,
            _ExecutionService_,
            _$rootScope_,
            _$q_
        ) {
            $controller = _$controller_;
            CampaignService = _CampaignService_;
            ExecutionService = _ExecutionService_;
            $rootScope = _$rootScope_;
            $q = _$q_;
        });

        ExecutionListHeaderController = $controller(BaseController, {
            CampaignService,
            ExecutionService
        });

        spyOn(CampaignService, "triggerAutomatedTests").and.returnValue($q.when());

        setSuccess = jasmine.createSpy("setSuccess");
        rewire$setSuccess(setSuccess);
        setError = jasmine.createSpy("setError");
        rewire$setError(setError);
        resetError = jasmine.createSpy("resetError");
        rewire$resetError(resetError);
    });

    afterEach(() => {
        restore();
    });

    describe("launchAutomatedTests() -", () => {
        beforeEach(() => {
            ExecutionService.campaign = {
                id: 42,
                job_configuration: {
                    url: "https://wambly.com/doghood/follow?a=menald&b=rebirth#coabode"
                }
            };
        });

        it("When the REST call succeeds, then the loader will be hidden and a success message will be shown", () => {
            ExecutionListHeaderController.launchAutomatedTests();

            expect(ExecutionListHeaderController.triggered).toBe(true);
            expect(resetError).toHaveBeenCalled();
            expect(CampaignService.triggerAutomatedTests).toHaveBeenCalledWith(42);

            $rootScope.$apply();
            expect(setSuccess).toHaveBeenCalled();
            expect(ExecutionListHeaderController.triggered).toBe(false);
        });

        it("When the REST call fails, then the loader will be hidden and an error message will be shown", () => {
            CampaignService.triggerAutomatedTests.and.returnValue(
                $q.reject({
                    message: "Message: The requested URL returned error: 403 Forbidden"
                })
            );

            ExecutionListHeaderController.launchAutomatedTests();
            $rootScope.$apply();

            expect(setError).toHaveBeenCalledWith(
                "Message: The requested URL returned error: 403 Forbidden"
            );
            expect(ExecutionListHeaderController.triggered).toBe(false);
        });
    });
});
