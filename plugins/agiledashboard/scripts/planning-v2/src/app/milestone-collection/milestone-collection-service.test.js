import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

describe("MilestoneCollectionService -", function () {
    var MilestoneCollectionService;

    beforeEach(function () {
        angular.mock.module(planning_module);

        angular.mock.inject(function (_MilestoneCollectionService_) {
            MilestoneCollectionService = _MilestoneCollectionService_;
        });
    });

    describe("addOrReorderBacklogItemsInMilestoneContent() -", function () {
        it("Given a milestone id, an array of items to add without comparedto, when I try insert it to the milestone content, then it will be inserted at index 0", function () {
            var milestone = {
                id: 8,
                content: [],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(
                8,
                [{ id: 69 }, { id: 70 }],
                null,
            );

            expect(milestone.content).toEqual([{ id: 69 }, { id: 70 }]);
        });

        it("Given a milestone id, an array of items to reorder and a comparedto object, when I try insert it to the milestone content, then it will be reordered", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(8, [{ id: 69 }], {
                item_id: 48,
                direction: "before",
            });

            expect(milestone.content).toEqual([{ id: 69 }, { id: 48 }]);
        });

        it("Given a milestone id, an array of items to add and a comparedto object, when I try insert it to the milestone content, then it will be inserted at the given index (after) in the milestone content", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(8, [{ id: 98 }], {
                item_id: 48,
                direction: "after",
            });

            expect(milestone.content).toEqual([{ id: 48 }, { id: 98 }, { id: 69 }]);
        });

        it("Given a milestone id, an array of items (multiple) to add and a comparedto object, when I try insert it to the milestone content, then it will be inserted at the given index (after last) inmilestone content", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(
                8,
                [{ id: 98 }, { id: 99 }],
                { item_id: 69, direction: "after" },
            );

            expect(milestone.content).toEqual([{ id: 48 }, { id: 69 }, { id: 98 }, { id: 99 }]);
        });

        it("Given a milestone id, an array of items to add and a comparedto object, when I try insert it to the milestone content, then it will be inserted at the given index (before) in themilestone content", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(8, [{ id: 98 }], {
                item_id: 69,
                direction: "before",
            });

            expect(milestone.content).toEqual([{ id: 48 }, { id: 98 }, { id: 69 }]);
        });

        it("Given a milestone id, an array of items to add and a comparedto object, when I try insert it to the milestone content, then it will be inserted at the given index (before first) milestone content", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.addOrReorderBacklogItemsInMilestoneContent(8, [{ id: 98 }], {
                item_id: 48,
                direction: "before",
            });

            expect(milestone.content).toEqual([{ id: 98 }, { id: 48 }, { id: 69 }]);
        });
    });

    describe("removeBacklogItemsFromMilestoneContent() -", function () {
        it("Given a milestone id and a array of items, when I remove them, then the milestone content will not have these items anymore", function () {
            var milestone = {
                id: 8,
                content: [{ id: 97 }, { id: 48 }, { id: 98 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.removeBacklogItemsFromMilestoneContent(8, [
                { id: 98 },
                { id: 97 },
            ]);

            expect(milestone.content).toEqual([{ id: 48 }, { id: 69 }]);
        });

        it("Given a milestone id and a array of items, when I try to remove them but they are not in the milestone content, then the milestone content won't change", function () {
            var milestone = {
                id: 8,
                content: [{ id: 48 }, { id: 69 }],
            };
            MilestoneCollectionService.milestones.content = [milestone];

            MilestoneCollectionService.removeBacklogItemsFromMilestoneContent(8, [{ id: 98 }]);

            expect(milestone.content).toEqual([{ id: 48 }, { id: 69 }]);
        });
    });
});
