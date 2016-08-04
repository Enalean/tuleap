describe("RestErrorService -", function() {
    var $modal,
        RestErrorService;

    beforeEach(function() {
        module('kanban', function($provide) {
            $provide.decorator('$modal', function($delegate) {
                spyOn($delegate, "open");

                return $delegate;
            });
        });

        inject(function(
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
