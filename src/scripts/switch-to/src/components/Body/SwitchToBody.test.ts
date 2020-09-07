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
import SwitchToBody from "./SwitchToBody.vue";
import { createStoreMock } from "../../../../vue-components/store-wrapper-jest";
import { Project, UserHistory } from "../../type";
import ListOfProjects from "./Projects/ListOfProjects.vue";
import ListOfRecentItems from "./RecentItems/ListOfRecentItems.vue";
import GlobalEmptyState from "./GlobalEmptyState.vue";
import GlobalLoadingState from "./GlobalLoadingState.vue";

describe("SwitchToBody", () => {
    it("Displays projects and recent items", () => {
        const wrapper = shallowMount(SwitchToBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_loading_history: false,
                        is_history_loaded: true,
                        history: { entries: [] } as UserHistory,
                        projects: [{}] as Project[],
                    },
                }),
            },
        });

        expect(wrapper.findComponent(GlobalLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GlobalEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(ListOfProjects).exists()).toBe(true);
        expect(wrapper.findComponent(ListOfRecentItems).exists()).toBe(true);
    });

    it("Displays loading state when there is no projects and the history is being loaded", () => {
        const wrapper = shallowMount(SwitchToBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_loading_history: true,
                        is_history_loaded: false,
                        history: { entries: [] } as UserHistory,
                        projects: [] as Project[],
                    },
                }),
            },
        });

        expect(wrapper.findComponent(GlobalLoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(GlobalEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(ListOfProjects).exists()).toBe(false);
        expect(wrapper.findComponent(ListOfRecentItems).exists()).toBe(false);
    });

    it("Displays empty state when there is no projects and no history", () => {
        const wrapper = shallowMount(SwitchToBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_loading_history: false,
                        is_history_loaded: true,
                        history: { entries: [] } as UserHistory,
                        projects: [] as Project[],
                    },
                }),
            },
        });

        expect(wrapper.findComponent(GlobalLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(GlobalEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(ListOfProjects).exists()).toBe(false);
        expect(wrapper.findComponent(ListOfRecentItems).exists()).toBe(false);
    });
});
