describe("AppController -", function() {
    var AppController;

    beforeEach(function() {
        module('tuleap.frs');

        var $controller;

        inject(function( // eslint-disable-line angular/di
            _$controller_
        ) {
            $controller = _$controller_;
        });

        AppController = $controller('AppController');
    });


    it("has an init function", function() {
        expect(AppController.init).toBeDefined();
    });
});
