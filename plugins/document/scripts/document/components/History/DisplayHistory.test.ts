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

import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { shallowMount } from "@vue/test-utils";
import DisplayHistory from "./DisplayHistory.vue";
import HistoryLogs from "./HistoryLogs.vue";
import HistoryVersions from "./HistoryVersions.vue";
import localVue from "../../helpers/local-vue";
import VueRouter from "vue-router";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("DisplayHistory", () => {
    let store = {
        dispatch: jest.fn(),
    };

    it.each([
        [TYPE_FOLDER, false],
        [TYPE_FILE, true],
        [TYPE_LINK, true],
        [TYPE_EMBEDDED, true],
        [TYPE_WIKI, false],
        [TYPE_EMPTY, false],
    ])(
        "should display a %s with versions: %s and logs",
        async (type, should_versions_be_displayed) => {
            const router = new VueRouter({
                routes: [
                    {
                        path: "/history/42",
                        name: "item",
                    },
                ],
            });

            store = createStoreMock({});

            store.dispatch.mockImplementation((action_name) => {
                if (action_name === "loadDocumentWithAscendentHierarchy") {
                    return {
                        id: 42,
                        type,
                    };
                }

                return null;
            });

            const wrapper = shallowMount(DisplayHistory, {
                localVue,
                router,
                mocks: { $store: store },
            });

            // wait for loadDocumentWithAscendentHierarchy() to be called
            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(HistoryVersions).exists()).toBe(
                should_versions_be_displayed
            );
            expect(wrapper.findComponent(HistoryLogs).exists()).toBe(true);
        }
    );
});
