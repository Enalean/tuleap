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
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ConfigurationState } from "../../../../store/configuration";
import CellUpdateDate from "./CellUpdateDate.vue";

describe("CellUpdateDate", () => {
    it("should display the update date of the item", () => {
        const owner: User = {
            id: 102,
            uri: "users/102",
        } as unknown as User;

        const wrapper = shallowMount(CellUpdateDate, {
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
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
