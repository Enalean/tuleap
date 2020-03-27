import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("BacklogItemFactory", function () {
    beforeEach(function () {
        angular.mock.module(planning_module);
    });

    describe("augment", function () {
        var item = {
            artifact: {
                tracker: {
                    id: 78,
                },
            },
            accept: {
                trackers: [{ id: 123 }, { id: 895 }],
            },
            status: "Open",
        };

        beforeEach(
            angular.mock.inject(function (BacklogItemFactory) {
                jest.spyOn(BacklogItemFactory, "augment");

                BacklogItemFactory.augment(item);
            })
        );

        it("adds allowed tracker types to backlog item", function () {
            expect(item.accepted_types.toString()).toEqual("trackerId123|trackerId895");
            expect(item.trackerId).toEqual("trackerId78");
        });

        it("adds children properties", function () {
            var expected = {
                data: [],
                loaded: false,
                collapsed: true,
            };

            expect(item.children).toEqual(expected);
        });

        it("has method isOpen", function () {
            expect(item.isOpen()).toEqual(true);
        });
    });
});
