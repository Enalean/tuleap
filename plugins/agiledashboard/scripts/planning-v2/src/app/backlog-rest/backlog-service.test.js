/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import * as factory from "../backlog-item-rest/backlog-item-factory";

describe("BacklogService", () => {
    let $q, $scope, $filter, BacklogService, ProjectService;

    beforeEach(() => {
        angular.mock.module(planning_module, function ($provide) {
            $provide.decorator("$filter", function () {
                return jest.fn(function () {
                    return function () {};
                });
            });

            $provide.decorator("ProjectService", function ($delegate) {
                jest.spyOn($delegate, "getProjectBacklog").mockImplementation(() => {});
                jest.spyOn($delegate, "getProject").mockImplementation(() => {});

                return $delegate;
            });
        });

        angular.mock.inject(
            function (_$q_, _$rootScope_, _$filter_, _BacklogService_, _ProjectService_) {
                $q = _$q_;
                $scope = _$rootScope_.$new();
                $filter = _$filter_;
                BacklogService = _BacklogService_;
                ProjectService = _ProjectService_;
            },
        );
    });

    describe("appendBacklogItems()", () => {
        it(`Given an array of items, when I append them to the backlog,
            then each item will be augmented using BacklogItemFactory and appended to the items' content,
            and the items object will no longer be marked as loading`, () => {
            let augment = jest
                .spyOn(factory, "augment")
                .mockImplementation((backlog_item) => backlog_item);
            BacklogService.items.content = [{ id: 37 }];

            BacklogService.appendBacklogItems([{ id: 64 }, { id: 13 }]);

            expect(BacklogService.items.content).toStrictEqual([
                { id: 37 },
                { id: 64 },
                { id: 13 },
            ]);
            expect(augment).toHaveBeenCalledWith({ id: 64 });
            expect(augment).toHaveBeenCalledWith({ id: 13 });
            expect(BacklogService.items.loading).toBeFalsy();
        });
    });

    describe("addOrReorderBacklogItemsInBacklog() -", function () {
        it("Given an existing backlog item and an index, when I append it to the backlog, then it will be inserted at the given index (after) in the backlog's items collection", function () {
            var initial_backlog = [{ id: 18 }, { id: 31 }];
            BacklogService.items.content = initial_backlog;

            BacklogService.addOrReorderBacklogItemsInBacklog([{ id: 98 }], {
                item_id: 18,
                direction: "after",
            });

            expect(BacklogService.items.content).toStrictEqual([
                { id: 18 },
                { id: 98 },
                { id: 31 },
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });

        it("Given an existing backlog item and an index, when I append it to the backlog, then it will be inserted at the given index (after last) in the backlog's items collection", function () {
            var initial_backlog = [{ id: 18 }, { id: 31 }];
            BacklogService.items.content = initial_backlog;

            BacklogService.addOrReorderBacklogItemsInBacklog([{ id: 98 }], {
                item_id: 31,
                direction: "after",
            });

            expect(BacklogService.items.content).toStrictEqual([
                { id: 18 },
                { id: 31 },
                { id: 98 },
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });

        it("Given an existing backlog item and an index, when I append it to the backlog, then it will be inserted at the given index (before) in the backlog's items collection", function () {
            var initial_backlog = [{ id: 18 }, { id: 31 }];
            BacklogService.items.content = initial_backlog;

            BacklogService.addOrReorderBacklogItemsInBacklog([{ id: 98 }], {
                item_id: 31,
                direction: "before",
            });

            expect(BacklogService.items.content).toStrictEqual([
                { id: 18 },
                { id: 98 },
                { id: 31 },
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });

        it("Given an existing backlog item and an index, when I append it to the backlog, then it will be inserted at the given index (before first) in the backlog's items collection", function () {
            var initial_backlog = [{ id: 18 }, { id: 31 }];
            BacklogService.items.content = initial_backlog;

            BacklogService.addOrReorderBacklogItemsInBacklog([{ id: 98 }], {
                item_id: 18,
                direction: "before",
            });

            expect(BacklogService.items.content).toStrictEqual([
                { id: 98 },
                { id: 18 },
                { id: 31 },
            ]);
            expect(BacklogService.items.content).toBe(initial_backlog);
        });
    });

    describe("removeBacklogItemsFromBacklog() -", function () {
        it("Given an item in the backlog's items collection and given this item's id, when I remove it from the backlog, then the item will no longer be in the backlog's items collection", function () {
            var initial_backlog = [{ id: 48 }, { id: 92 }, { id: 69 }];
            BacklogService.items.content = initial_backlog;
            BacklogService.items.filtered_content = initial_backlog;

            BacklogService.removeBacklogItemsFromBacklog([{ id: 92 }]);

            expect(BacklogService.items.content).toStrictEqual([{ id: 48 }, { id: 69 }]);
            expect(BacklogService.items.filtered_content).toStrictEqual([{ id: 48 }, { id: 69 }]);
        });

        it("Given an item that was not in the backlog's items collection, when I remove it, then the the backlog's items collection won't change", function () {
            var initial_backlog = [{ id: 48 }, { id: 69 }];
            BacklogService.items.content = initial_backlog;
            BacklogService.items.filtered_content = initial_backlog;

            BacklogService.removeBacklogItemsFromBacklog([{ id: 92 }]);

            expect(BacklogService.items.content).toStrictEqual([{ id: 48 }, { id: 69 }]);
            expect(BacklogService.items.filtered_content).toStrictEqual([{ id: 48 }, { id: 69 }]);
        });
    });

    describe("filterItems() -", function () {
        it("Given filter terms that did not match anything, when I filter backlog items, then the InPropertiesFilter will be called and the items' filtered content collection will be emptied", function () {
            BacklogService.items.content = [{ id: 37 }];
            var filtered_content_ref = BacklogService.items.filtered_content;

            BacklogService.filterItems("reagreement");

            expect($filter).toHaveBeenCalledWith("InPropertiesFilter");
            expect(BacklogService.items.filtered_content).toBe(filtered_content_ref);
            expect(BacklogService.items.filtered_content).toHaveLength(0);
        });

        it("Given filter terms that matched items, when I filter backlog items, then the InPropertiesFilter will be called and the items' filtered content collection will be updated", function () {
            BacklogService.items.content = [{ id: 46 }, { id: 37 }, { id: 62 }];
            $filter.mockImplementation(function () {
                return function () {
                    return [{ id: 46 }, { id: 62 }];
                };
            });

            BacklogService.filterItems("6");

            expect($filter).toHaveBeenCalledWith("InPropertiesFilter");
            expect(BacklogService.items.filtered_content).toStrictEqual([{ id: 46 }, { id: 62 }]);
        });
    });

    describe("loadProjectBacklog() -", function () {
        it("Given a project id, when I load the project backlog, then ProjectService will be called and the backlog object will be updated", function () {
            var project_request = $q.defer();
            var project_backlog_request = $q.defer();
            ProjectService.getProject.mockReturnValue(project_request.promise);
            ProjectService.getProjectBacklog.mockReturnValue(project_backlog_request.promise);

            BacklogService.loadProjectBacklog(736);
            project_request.resolve({
                data: {
                    additional_informations: {
                        agiledashboard: {
                            root_planning: {
                                milestone_tracker: {
                                    id: 218,
                                    label: "Releases",
                                },
                            },
                        },
                    },
                },
            });
            project_backlog_request.resolve({
                allowed_backlog_item_types: {
                    content: [{ id: 5, label: "Epic" }],
                },
                has_user_priority_change_permission: true,
            });
            $scope.$apply();

            expect(ProjectService.getProject).toHaveBeenCalledWith(736);
            expect(ProjectService.getProjectBacklog).toHaveBeenCalledWith(736);
            expect(BacklogService.backlog).toStrictEqual({
                rest_base_route: "projects",
                rest_route_id: 736,
                current_milestone: undefined,
                original_project: undefined,
                submilestone_type: {
                    id: 218,
                    label: "Releases",
                },
                accepted_types: {
                    content: [{ id: 5, label: "Epic" }],
                },
                user_can_move_cards: true,
            });
            expect(BacklogService.backlog.rest_base_route).toBe("projects");
            expect(BacklogService.backlog.rest_route_id).toBe(736);
            expect(BacklogService.backlog.current_milestone).toBeUndefined();
            expect(BacklogService.backlog.submilestone_type).toStrictEqual({
                id: 218,
                label: "Releases",
            });
            expect(BacklogService.backlog.accepted_types.content).toStrictEqual([
                { id: 5, label: "Epic" },
            ]);
            expect(BacklogService.backlog.user_can_move_cards).toBeTruthy();
        });
    });

    describe("loadMilestoneBacklog() -", function () {
        it("Given a milestone, when I load its backlog, then the backlog object will be updated", function () {
            var milestone = {
                id: 592,
                backlog_accepted_types: {
                    content: [{ id: 72, label: "User Stories" }],
                },
                sub_milestone_type: { id: 66, label: "Sprints" },
                has_user_priority_change_permission: true,
                original_project_provider: { id: 101, label: "other project" },
            };

            BacklogService.loadMilestoneBacklog(milestone);

            expect(BacklogService.backlog.rest_base_route).toBe("milestones");
            expect(BacklogService.backlog.rest_route_id).toBe(592);
            expect(BacklogService.backlog.current_milestone).toBe(milestone);
            expect(BacklogService.backlog.submilestone_type).toStrictEqual({
                id: 66,
                label: "Sprints",
            });
            expect(BacklogService.backlog.accepted_types.content).toStrictEqual([
                { id: 72, label: "User Stories" },
            ]);
            expect(BacklogService.backlog.user_can_move_cards).toBeTruthy();
            expect(BacklogService.backlog.original_project).toStrictEqual({
                id: 101,
                label: "other project",
            });
        });
    });
});
