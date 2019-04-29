import testmanagement_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("SocketService -", () => {
    let SocketService, SocketFactory, ExecutionService;

    beforeEach(() => {
        angular.mock.module(testmanagement_module);

        angular.mock.inject(function(_SocketFactory_, _SocketService_, _ExecutionService_) {
            SocketFactory = _SocketFactory_;
            SocketService = _SocketService_;
            ExecutionService = _ExecutionService_;
        });

        SocketFactory.on = jasmine.createSpy("on");
    });

    describe("listenToArtifactLinked() -", () => {
        it("When an execution is linked to artifacts, then the execution will be updated with the newly linked artifacts", () => {
            spyOn(ExecutionService, "addArtifactLink");
            const artifact_id = 87;
            const added_artifact_link = { id: 53, title: "visuosensory" };
            SocketFactory.on.and.callFake((event, callback) => {
                callback({
                    artifact_id,
                    added_artifact_link
                });
            });

            SocketService.listenToArtifactLinked();

            expect(SocketFactory.on).toHaveBeenCalledWith(
                "testmanagement_execution:link_artifact",
                jasmine.any(Function)
            );
            expect(ExecutionService.addArtifactLink).toHaveBeenCalledWith(
                artifact_id,
                added_artifact_link
            );
        });
    });
});
