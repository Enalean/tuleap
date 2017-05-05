import kanban_module from './app.js';
import angular from 'angular';
import 'angular-mocks';

describe("RestErrorService -", function() {
    var $modal,
        RestErrorService;

    beforeEach(function() {
        angular.mock.module(kanban_module, function($provide) {
            $provide.decorator('$modal', function($delegate) {
                spyOn($delegate, "open");

                return $delegate;
            });
        });

        angular.mock.inject(function(
            _$modal_,
            _RestErrorService_
        ) {
            $modal           = _$modal_;
            RestErrorService = _RestErrorService_;
        });
    });

    describe("reload() -", function() {
        it("Given a REST error response, then a modal will be opened to inform the user that she must reload the page", function() {
            var response = {
                data: {
                    error: {
                        code   : 401,
                        message: 'Unauthorized'
                    }
                }
            };

            RestErrorService.reload(response);

            expect($modal.open).toHaveBeenCalled();
        });
    });
});
