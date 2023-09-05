import testmanagement_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import io from "socket.io-client";

jest.mock("socket.io-client");

describe("SocketService -", () => {
    let SocketService, SocketFactory, ExecutionService;

    beforeEach(() => {
        io.mockReturnValue({});
        angular.mock.module(testmanagement_module);

        angular.mock.inject(function (_SocketFactory_, _SocketService_, _ExecutionService_) {
            SocketFactory = _SocketFactory_;
            SocketService = _SocketService_;
            ExecutionService = _ExecutionService_;
        });
    });

    describe("listenToArtifactLinked() -", () => {
        it("When an execution is linked to artifacts, then the execution will be updated with the newly linked artifacts", () => {
            jest.spyOn(ExecutionService, "addArtifactLink").mockImplementation(() => {});
            const artifact_id = 87;
            const added_artifact_link = { id: 53, title: "visuosensory" };
            const socket_factory_on = jest
                .spyOn(SocketFactory, "on")
                .mockImplementation((event, callback) => {
                    callback({
                        artifact_id,
                        added_artifact_link,
                    });
                });

            SocketService.listenToArtifactLinked();

            expect(socket_factory_on).toHaveBeenCalledWith(
                "testmanagement_execution:link_artifact",
                expect.any(Function),
            );
            expect(ExecutionService.addArtifactLink).toHaveBeenCalledWith(
                artifact_id,
                added_artifact_link,
            );
        });
    });
});
