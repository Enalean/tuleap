describe("BacklogController - ", function() {
    var BacklogController, BacklogService;

    beforeEach(function() {
        module('backlog');

        inject(function(
            $controller,
            _BacklogService_
        ) {
            BacklogService = _BacklogService_;

            BacklogController = $controller('BacklogController', {
                BacklogService: BacklogService
            });
        });
    });

    describe("displayUserCantPrioritize() -", function() {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I check, then it will return false", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I check, then it will return true", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [
                { id: 448 }
            ];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeTruthy();
        });
    });
});
