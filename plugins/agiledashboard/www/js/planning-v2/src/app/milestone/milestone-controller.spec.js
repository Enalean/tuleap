describe("MilestoneController -", function() {
    var MilestoneController;

    beforeEach(function() {
        module('milestone');

        inject(function(
            $controller
        ) {
            MilestoneController = $controller('MilestoneController', {
            });
        });
    });

    describe("toggleMilestone() -", function() {
        var event, milestone;
        describe("Given an event with a target that was not a create-item-link and a milestone object", function() {
            beforeEach(function() {
                event = {
                    target: {
                        classList: {
                            contains: function() {
                                return false;
                            }
                        }
                    }
                };
            });

            it("that was already loaded and collapsed, when I toggle a milestone, then it will be un-collapsed", function() {
                milestone = {
                    collapsed: true,
                    alreadyLoaded: true
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.collapsed).toBeFalsy();
            });

            it("that was already loaded and was not collapsed, when I toggle a milestone, then it will be collapsed", function() {
                milestone = {
                    collapsed: false,
                    alreadyLoaded: true
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.collapsed).toBeTruthy();
            });

            it("that was not already loaded, when I toggle a milestone, then its content will be loaded", function() {
                milestone = {
                    content: [],
                    getContent: jasmine.createSpy("getContent")
                };

                MilestoneController.toggleMilestone(event, milestone);

                expect(milestone.getContent).toHaveBeenCalled();
            });
        });

        it("Given an event with a create-item-link target and a collapsed milestone, when I toggle a milestone, then it will stay collapsed", function() {
            event = {
                target: {
                    parentNode: {
                        getElementsByClassName: function() {
                            return [
                                {
                                    fakeElement: ''
                                }
                            ];
                        }
                    }
                }
            };

            milestone = {
                collapsed: true,
                alreadyLoaded: true
            };

            MilestoneController.toggleMilestone(event, milestone);

            expect(milestone.collapsed).toBeTruthy();
        });
    });
});
