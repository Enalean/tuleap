/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { BacklogItemState } from "./type";
import { BacklogItem, TestDefinition } from "../../type";
import {
    addBacklogItems,
    addTestDefinitions,
    beginLoadingBacklogItems,
    beginLoadingTestDefinition,
    collapseBacklogItem,
    endLoadingBacklogItems,
    endLoadingTestDefinition,
    expandBacklogItem,
    loadingErrorHasBeenCatched,
    loadingErrorHasBeenCatchedForTestDefinition,
    markTestDefinitionsAsBeingLoaded,
    removeIsJustRefreshedFlagOnBacklogItem,
    removeIsJustRefreshedFlagOnTestDefinition,
} from "./backlog-item-mutations";

jest.useFakeTimers();

describe("BacklogItem state mutations", () => {
    it("beginLoadingBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: false,
            has_loading_error: false,
            backlog_items: [{ id: 1 } as BacklogItem],
        };

        beginLoadingBacklogItems(state);

        expect(state.is_loading).toBe(true);
        expect(state.backlog_items).toStrictEqual([]);
    });

    it("endLoadingBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [],
        };

        endLoadingBacklogItems(state);

        expect(state.is_loading).toBe(false);
    });

    it("addBacklogItems", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [{ id: 1 } as BacklogItem],
        };

        addBacklogItems(state, [{ id: 2 }, { id: 3 }] as BacklogItem[]);

        expect(state.backlog_items.length).toBe(3);
    });

    it("loadingErrorHasBeenCatched", () => {
        const state: BacklogItemState = {
            is_loading: true,
            has_loading_error: false,
            backlog_items: [],
        };

        loadingErrorHasBeenCatched(state);

        expect(state.has_loading_error).toBe(true);
    });

    describe("expandBacklogItem", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                expandBacklogItem(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("Expands the item", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, is_expanded: false } as BacklogItem],
            };

            expandBacklogItem(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items).toStrictEqual([{ id: 123, is_expanded: true }]);
        });
    });

    describe("collapseBacklogItem", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                collapseBacklogItem(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("Collapses the item", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, is_expanded: true } as BacklogItem],
            };

            collapseBacklogItem(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items).toStrictEqual([{ id: 123, is_expanded: false }]);
        });
    });

    describe("beginLoadingTestDefinition", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                beginLoadingTestDefinition(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("Begins the loading of test definitions", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, is_loading_test_definitions: false } as BacklogItem],
            };

            beginLoadingTestDefinition(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items[0].is_loading_test_definitions).toBe(true);
        });
    });

    describe("endLoadingTestDefinition", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                endLoadingTestDefinition(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("Ends the loading of test definitions", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, is_loading_test_definitions: true } as BacklogItem],
            };

            endLoadingTestDefinition(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items[0].is_loading_test_definitions).toBe(false);
        });
    });

    describe("loadingErrorHasBeenCatchedForTestDefinition", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                loadingErrorHasBeenCatchedForTestDefinition(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("flags the loading of test definitions as error", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    { id: 123, has_test_definitions_loading_error: false } as BacklogItem,
                ],
            };

            loadingErrorHasBeenCatchedForTestDefinition(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items[0].has_test_definitions_loading_error).toBe(true);
        });
    });

    describe("removeIsJustRefreshedFlagOnBacklogItem", () => {
        it("removes juste refreshed flag on backlog item", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, is_just_refreshed: true } as BacklogItem],
            };

            removeIsJustRefreshedFlagOnBacklogItem(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items[0].is_just_refreshed).toBe(false);
        });

        it("throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: false,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                removeIsJustRefreshedFlagOnBacklogItem(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });
    });

    describe("markTestDefinitionsAsBeingLoaded", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                markTestDefinitionsAsBeingLoaded(state, { id: 123 } as BacklogItem);
            }).toThrow();
        });

        it("flags test definitions as being loaded", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [{ id: 123, are_test_definitions_loaded: false } as BacklogItem],
            };

            markTestDefinitionsAsBeingLoaded(state, { id: 123 } as BacklogItem);

            expect(state.backlog_items[0].are_test_definitions_loaded).toBe(true);
        });
    });

    describe("addTestDefinitions", () => {
        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                addTestDefinitions(state, {
                    backlog_item: { id: 123 } as BacklogItem,
                    test_definitions: [{ id: 678 } as TestDefinition],
                });
            }).toThrow();
        });

        it("Adds test definitions to the backlog item", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    { id: 123, test_definitions: [{ id: 677 } as TestDefinition] } as BacklogItem,
                ],
            };

            addTestDefinitions(state, {
                backlog_item: { id: 123 } as BacklogItem,
                test_definitions: [{ id: 678 } as TestDefinition],
            });

            expect(state.backlog_items[0].test_definitions.length).toBe(2);
        });

        it("Moves not planned test definitions to the end of the definitions of a backlog item", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    {
                        id: 123,
                        test_definitions: [
                            { id: 677, test_status: null } as TestDefinition,
                            { id: 679, test_status: "passed" } as TestDefinition,
                            { id: 678, test_status: null } as TestDefinition,
                        ],
                    } as BacklogItem,
                ],
            };

            addTestDefinitions(state, {
                backlog_item: { id: 123 } as BacklogItem,
                test_definitions: [{ id: 680, test_status: "blocked" } as TestDefinition],
            });

            expect(state.backlog_items[0].test_definitions).toStrictEqual([
                { id: 679, test_status: "passed" },
                { id: 680, test_status: "blocked" },
                { id: 677, test_status: null },
                { id: 678, test_status: null },
            ]);
        });

        it("Does not wipe new test definitions if we call another mutation", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    { id: 123, test_definitions: [{ id: 677 } as TestDefinition] } as BacklogItem,
                ],
            };

            const backlog_item = { id: 123 } as BacklogItem;
            addTestDefinitions(state, {
                backlog_item,
                test_definitions: [{ id: 678 } as TestDefinition],
            });
            endLoadingTestDefinition(state, backlog_item);

            expect(state.backlog_items[0].test_definitions.length).toBe(2);
        });
    });

    describe("removeIsJustRefreshedFlagOnTestDefinition", () => {
        it("Remove is_just_refreshed flag", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    {
                        id: 123,
                        test_definitions: [{ id: 1001, is_just_refreshed: true }],
                    } as BacklogItem,
                ],
            };

            removeIsJustRefreshedFlagOnTestDefinition(state, {
                backlog_item: { id: 123 } as BacklogItem,
                test_definition: { id: 1001 } as TestDefinition,
            });

            expect(state.backlog_items[0].test_definitions[0].is_just_refreshed).toBe(false);
        });

        it("Throws error if backlog item cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [],
            };

            expect(() => {
                removeIsJustRefreshedFlagOnTestDefinition(state, {
                    backlog_item: { id: 123 } as BacklogItem,
                    test_definition: { id: 1001 } as TestDefinition,
                });
            }).toThrow();
        });

        it("Throws error if test definition cannot be found", () => {
            const state: BacklogItemState = {
                is_loading: true,
                has_loading_error: false,
                backlog_items: [
                    {
                        id: 123,
                        test_definitions: [] as TestDefinition[],
                    } as BacklogItem,
                ],
            };

            expect(() => {
                removeIsJustRefreshedFlagOnTestDefinition(state, {
                    backlog_item: { id: 123 } as BacklogItem,
                    test_definition: { id: 1001 } as TestDefinition,
                });
            }).toThrow();
        });
    });
});
