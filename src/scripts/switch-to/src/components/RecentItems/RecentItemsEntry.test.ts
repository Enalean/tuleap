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
import { QuickLink, UserHistoryEntry } from "../../type";
import RecentItemsEntry from "./RecentItemsEntry.vue";

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
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
