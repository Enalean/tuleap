/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { ItemSearchResult, User } from "../../../../type";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import CellLocation from "./CellLocation.vue";

describe("CellLocation", () => {
    it("should display path to the item", () => {
        const owner: User = {
            id: 102,
            uri: "users/102",
        } as unknown as User;

        const wrapper = shallowMount(CellLocation, {
            localVue,
            propsData: {
                item: {
                    id: 123,
                    type: "folder",
                    title: "Lorem",
                    post_processed_description: "ipsum doloret",
                    owner,
                    last_update_date: "2021-10-06",
                    parents: [
                        {
                            id: 120,
                            title: "Path",
                        },
                        {
                            id: 121,
                            title: "To",
                        },
                        {
                            id: 122,
                            title: "Folder",
                        },
                    ],
                    file_properties: null,
                } as ItemSearchResult,
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.text()).toContain("Path/To/Folder");
    });
});
