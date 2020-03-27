import angular from "angular";
import "angular-mocks";
import error_modal_module from "./error-modal.js";

describe("RestErrorService -", function () {
    var RestErrorService, TlpModalService;

    beforeEach(function () {
        angular.mock.module(error_modal_module, function ($provide) {
            $provide.decorator("TlpModalService", function ($delegate) {
                jest.spyOn($delegate, "open").mockImplementation(() => {});

                return $delegate;
            });
        });

        angular.mock.inject(function (_TlpModalService_, _RestErrorService_) {
            TlpModalService = _TlpModalService_;
            RestErrorService = _RestErrorService_;
        });
    });

    describe("reload() -", function () {
        it("Given a REST error response, then a modal will be opened to inform the user that she must reload the page", function () {
            var response = {
                data: {
                    error: {
                        code: 401,
                        message: "Unauthorized",
                    },
                },
            };

            RestErrorService.reload(response);

            expect(TlpModalService.open).toHaveBeenCalled();
        });
    });
});
