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
import { QuickLink, UserHistoryEntry } from "../../../type";
import RecentItemsEntry from "./RecentItemsEntry.vue";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";

describe("RecentItemsEntry", () => {
    it("Displays a link with a cross ref", () => {
        const wrapper = shallowMount(RecentItemsEntry, {
            propsData: {
                entry: {
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [] as QuickLink[],
                } as UserHistoryEntry,
                has_programmatically_focus: false,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a link with a quick links", () => {
        const wrapper = shallowMount(RecentItemsEntry, {
            propsData: {
                entry: {
                    xref: "art #123",
                    title: "Lorem ipsum",
                    color_name: "lake-placid-blue",
                    quick_links: [
                        { name: "TTM", icon_name: "fa-check", html_url: "/ttm" },
                        { name: "AD", icon_name: "fa-table", html_url: "/ad" },
                    ],
                } as UserHistoryEntry,
                has_programmatically_focus: false,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a link with an icon", () => {
        const wrapper = shallowMount(RecentItemsEntry, {
            propsData: {
                entry: {
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as QuickLink[],
                } as UserHistoryEntry,
                has_programmatically_focus: false,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Changes the focus with arrow keys", async () => {
        const entry = {
            icon_name: "fa-columns",
            title: "Kanban",
            color_name: "lake-placid-blue",
            quick_links: [] as QuickLink[],
        } as UserHistoryEntry;

        const wrapper = shallowMount(RecentItemsEntry, {
            propsData: {
                entry,
                has_programmatically_focus: false,
            },
            mocks: {
                $store: createStoreMock({
                    state: {} as State,
                }),
            },
        });

        const key = "ArrowUp";
        await wrapper.trigger("keydown", { key });

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("changeFocusFromHistory", {
            entry,
            key,
        });
    });

    it("Forces the focus from the outside", async () => {
        const wrapper = shallowMount(RecentItemsEntry, {
            propsData: {
                entry: {
                    icon_name: "fa-columns",
                    title: "Kanban",
                    color_name: "lake-placid-blue",
                    quick_links: [] as QuickLink[],
                } as UserHistoryEntry,
                has_programmatically_focus: false,
            },
        });

        const link = wrapper.find("[data-test=entry-link]");
        if (!(link.element instanceof HTMLAnchorElement)) {
            throw Error("Unable to find the link");
        }

        const focus = jest.spyOn(link.element, "focus");

        wrapper.setProps({ has_programmatically_focus: true });
        await wrapper.vm.$nextTick();

        expect(focus).toHaveBeenCalled();
    });
});
