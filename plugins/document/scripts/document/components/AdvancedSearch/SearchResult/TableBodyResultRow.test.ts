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

import { shallowMount } from "@vue/test-utils";
import TableBodyResultRow from "./TableBodyResultRow.vue";
import type { ConfigurationState } from "../../../store/configuration";
import type { ItemSearchResult, User } from "../../../type";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import localVue from "../../../helpers/local-vue";

describe("TableBodyResultRow", () => {
    it("should display an item as a table row", () => {
        const owner: User = {
            id: 102,
            uri: "users/102",
        } as unknown as User;

        const wrapper = shallowMount(TableBodyResultRow, {
            localVue,
            propsData: {
                item: {
                    id: 123,
                    title: "Lorem",
                    post_processed_description: "ipsum doloret",
                    owner,
                    last_update_date: "2021-10-06",
                } as ItemSearchResult,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper).toMatchSnapshot();
    });
});
