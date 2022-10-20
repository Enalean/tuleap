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
import localVue from "../../helpers/local-vue";
import HistoryVersionsContent from "./HistoryVersionsContent.vue";
import type { RestUser } from "../../api/rest-querier";
import type { FileHistory } from "../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../store/configuration";

describe("HistoryVersionsContent", () => {
    it("should display a link to the approval table", () => {
        const wrapper = shallowMount(HistoryVersionsContent, {
            localVue,
            propsData: {
                versions: [
                    {
                        id: 1,
                        name: "Plop",
                        changelog: "The changelog",
                        filename: "duck.png",
                        download_href: "/path/to/dl",
                        approval_href: "/path/to/table",
                        date: "2021-10-06",
                        author: { id: 102 } as unknown as RestUser,
                    } as FileHistory,
                ],
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

        expect(wrapper.find("[data-test=approval-link]").exists()).toBe(true);
    });
});
