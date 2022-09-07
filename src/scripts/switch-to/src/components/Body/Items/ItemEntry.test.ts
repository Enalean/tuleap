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

import { shallowMount } from "@vue/test-utils";
import type { QuickLink, ItemDefinition } from "../../../type";
import ItemEntry from "./ItemEntry.vue";
import { createTestingPinia } from "@pinia/testing";
import { createSwitchToLocalVue } from "../../../helpers/local-vue-for-test";
import { defineStore } from "pinia";
import type { State } from "../../../stores/type";

describe("ItemEntry", () => {
    it("Displays a link with a cross ref", async () => {
        const wrapper = shallowMount(ItemEntry, {
            propsData: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as QuickLink[],
                    project: {
                        label: "Guinea Pig",
                    },
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
            },
            pinia: createTestingPinia(),
            localVue: await createSwitchToLocalVue(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a link with a quick links", async () => {
        const wrapper = shallowMount(ItemEntry, {
            propsData: {
                entry: {
                    icon_name: "fa-columns",
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [
                        { name: "TTM", icon_name: "fa-check", html_url: "/ttm" },
                        { name: "AD", icon_name: "fa-table", html_url: "/ad" },
                    ],
                    project: {
                        label: "Guinea Pig",
                    },
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
            },
            pinia: createTestingPinia(),
            localVue: await createSwitchToLocalVue(),
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a link with an icon", async () => {
        const wrapper = shallowMount(ItemEntry, {
            propsData: {
                entry: {
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as QuickLink[],
                    project: {
                        label: "Guinea Pig",
                    },
                } as ItemDefinition,
                changeFocusCallback: jest.fn(),
            },
            pinia: createTestingPinia(),
            localVue: await createSwitchToLocalVue(),
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
                quick_links: [] as QuickLink[],
                project: {
                    label: "Guinea Pig",
                },
            } as ItemDefinition;

            const changeFocusCallback = jest.fn();
            const wrapper = shallowMount(ItemEntry, {
                propsData: {
                    entry,
                    changeFocusCallback,
                },
                pinia: createTestingPinia(),
                localVue: await createSwitchToLocalVue(),
            });

            await wrapper.trigger("keydown", { key });

            expect(changeFocusCallback).toHaveBeenCalledWith({
                entry,
                key,
            });
        }
    );

    it("Forces the focus from the outside", async () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    programmatically_focused_element: null,
                } as State),
        });

        const pinia = createTestingPinia();
        const store = useSwitchToStore(pinia);

        const entry = {
            icon_name: "fa-columns",
            title: "Kanban",
            color_name: "lake-placid-blue",
            quick_links: [] as QuickLink[],
            project: {
                label: "Guinea Pig",
            },
        } as ItemDefinition;

        const wrapper = shallowMount(ItemEntry, {
            propsData: {
                entry,
                changeFocusCallback: jest.fn(),
            },
            pinia,
            localVue: await createSwitchToLocalVue(),
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
});
