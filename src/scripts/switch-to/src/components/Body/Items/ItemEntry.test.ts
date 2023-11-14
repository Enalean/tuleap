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

import { afterEach, beforeEach, describe, expect, it, jest } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import type { ItemBadge, ItemDefinition, QuickLink } from "../../../type";
import ItemEntry from "./ItemEntry.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import type { KeyboardNavigationState } from "../../../stores/type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { cleanup, configure, render, waitFor } from "@testing-library/vue";
import { default as ItemBadgeComponent } from "./ItemBadge.vue";

describe("ItemEntry", () => {
    beforeEach(() => {
        configure({
            testIdAttribute: "data-test",
        });
    });

    afterEach(() => {
        cleanup();
    });

    it("Displays a link with a cross ref", () => {
        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
            global: getGlobalTestOptions(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should display only the first additional badge", () => {
        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [
                        { label: "On going", color: null },
                        { label: "Other", color: null },
                    ] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
            global: getGlobalTestOptions(),
        });

        const additional_badges = wrapper.findAllComponents(ItemBadgeComponent);
        expect(additional_badges).toHaveLength(1);
        expect(additional_badges[0].props().badge).toStrictEqual({
            label: "On going",
            color: null,
        });
    });

    it("Displays a link with a quick links", () => {
        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [
                        { name: "TTM", icon_name: "fa-check", html_url: "/ttm" },
                        { name: "AD", icon_name: "fa-table", html_url: "/ad" },
                    ] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
            global: getGlobalTestOptions(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a link with an icon", () => {
        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
            global: getGlobalTestOptions(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it.each(["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"])(
        "Changes the focus with arrow key %s",
        async (key) => {
            const entry = {
                icon_name: "fa-columns",
                title: "Kanban",
                color_name: "lake-placid-blue",
                quick_links: [] as ReadonlyArray<QuickLink>,
                project: {
                    label: "Guinea Pig",
                },
                badges: [] as ReadonlyArray<ItemBadge>,
            } as ItemDefinition;

            const changeFocusCallback = jest.fn();
            const wrapper = shallowMount(ItemEntry, {
                props: {
                    entry,
                    changeFocusCallback,
                    location: window.location,
                },
                global: getGlobalTestOptions(),
            });

            await wrapper.find("[data-test=entry-link]").trigger("keydown", { key });

            expect(changeFocusCallback).toHaveBeenCalledWith({
                entry,
                key,
            });
        },
    );

    it("Forces the focus from the outside", async () => {
        const useKeyboardNavigationStore = defineStore("keyboard-navigation", {
            state: (): KeyboardNavigationState =>
                ({
                    programmatically_focused_element: null,
                }) as KeyboardNavigationState,
        });

        const pinia = createTestingPinia();
        const store = useKeyboardNavigationStore(pinia);

        const entry = {
            icon_name: "fa-columns",
            title: "Kanban",
            color_name: "lake-placid-blue",
            quick_links: [] as ReadonlyArray<QuickLink>,
            project: {
                label: "Guinea Pig",
            },
            badges: [] as ReadonlyArray<ItemBadge>,
        } as ItemDefinition;

        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
            global: getGlobalTestOptions(pinia),
        });

        const link = wrapper.find("[data-test=entry-link]");
        if (!(link.element instanceof HTMLAnchorElement)) {
            throw Error("Unable to find the link");
        }

        const focus = jest.spyOn(link.element, "focus");

        await store.$patch({
            programmatically_focused_element: entry,
        });

        expect(focus).toHaveBeenCalled();
    });

    it("should highlight xref if it matches keywords", async () => {
        const { getByTestId, queryByTestId } = render(ItemEntry, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "plop",
                        },
                    },
                }),
            ),
            props: {
                entry: {
                    xref: "plop #123",
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                    cropped_content: null,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
        });

        expect(getByTestId("item-xref").querySelector("mark")).toBeTruthy();
        expect(getByTestId("item-title").querySelector("mark")).toBeFalsy();

        // We need to wait for child component to emit the 'matches' event
        await waitFor(() => expect(queryByTestId("item-content-matches")).toBeFalsy());
    });

    it("should highlight title if it matches keywords", async () => {
        const { getByTestId, queryByTestId } = render(ItemEntry, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "kanban",
                        },
                    },
                }),
            ),
            props: {
                entry: {
                    xref: "plop #123",
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                    cropped_content: null,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
        });

        expect(getByTestId("item-xref").querySelector("mark")).toBeFalsy();
        expect(getByTestId("item-title").querySelector("mark")).toBeTruthy();

        // We need to wait for child component to emit the 'matches' event
        await waitFor(() => expect(queryByTestId("item-content-matches")).toBeFalsy());
    });

    it("should display 'Content matches' when item is displayed without excerpt and neither xref nor title match the keywords", async () => {
        const { getByTestId, queryByTestId } = render(ItemEntry, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "unmatch",
                        },
                    },
                }),
            ),
            props: {
                entry: {
                    xref: "plop #123",
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                    cropped_content: null,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
        });

        expect(getByTestId("item-xref").querySelector("mark")).toBeFalsy();
        expect(getByTestId("item-title").querySelector("mark")).toBeFalsy();

        // We need to wait for child component to emit the 'matches' event
        await waitFor(() => expect(queryByTestId("item-content-matches")).toBeTruthy());
    });

    it("should display the cropped content instead of 'Content matches'", async () => {
        const { getByTestId, queryByTestId, getByText } = render(ItemEntry, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "unmatch",
                        },
                    },
                }),
            ),
            props: {
                entry: {
                    xref: "plop #123",
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                    cropped_content: "... excerpt ...",
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
        });

        expect(getByTestId("item-xref").querySelector("mark")).toBeFalsy();
        expect(getByTestId("item-title").querySelector("mark")).toBeFalsy();
        expect(getByText("... excerpt ...")).toBeTruthy();

        // We need to wait for child component to emit the 'matches' event
        await waitFor(() => expect(queryByTestId("item-content-matches")).toBeFalsy());
    });

    it("should not display the cropped content when xref or title match", async () => {
        const { getByTestId, queryByTestId, queryByText } = render(ItemEntry, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "plop",
                        },
                    },
                }),
            ),
            props: {
                entry: {
                    xref: "plop #123",
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                    cropped_content: "... excerpt ...",
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location: window.location,
            },
        });

        expect(getByTestId("item-xref").querySelector("mark")).toBeTruthy();
        expect(getByTestId("item-title").querySelector("mark")).toBeFalsy();

        // We need to wait for child component to emit the 'matches' event
        await waitFor(() => expect(queryByTestId("item-content-matches")).toBeFalsy());
        expect(queryByText("... excerpt ...")).toBeFalsy();
    });

    it("should go to the item when I click on the container", async () => {
        const location = { ...window.location, assign: jest.fn() };

        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location,
            },
            global: getGlobalTestOptions(),
        });

        await wrapper.find("[data-test=switch-to-item-entry]").trigger("click");
        expect(location.assign).toHaveBeenCalled();
    });

    it("should not manually assign the location when the real link is clicked", async () => {
        const location = { ...window.location, assign: jest.fn() };

        const wrapper = shallowMount(ItemEntry, {
            props: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                    project: {
                        label: "Guinea Pig",
                    },
                    badges: [] as ReadonlyArray<ItemBadge>,
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
                location,
            },
            global: getGlobalTestOptions(),
        });

        await wrapper.find("[data-test=entry-link]").trigger("click");
        expect(location.assign).not.toHaveBeenCalled();
    });
});
