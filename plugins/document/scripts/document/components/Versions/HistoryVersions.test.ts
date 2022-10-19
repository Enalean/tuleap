/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
const getAllFileVersionHistory = jest.fn();
jest.mock("../../api/version-rest-querier", () => {
    return {
        getAllFileVersionHistory,
    };
});

import { errAsync, okAsync } from "neverthrow";
import HistoryVersions from "./HistoryVersions.vue";
import HistoryVersionsLoadingState from "./HistoryVersionsLoadingState.vue";
import HistoryVersionsErrorState from "./HistoryVersionsErrorState.vue";
import HistoryVersionsEmptyState from "./HistoryVersionsEmptyState.vue";
import HistoryVersionsContent from "./HistoryVersionsContent.vue";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";
import type { FileHistory, ItemFile } from "../../type";

describe("HistoryVersions", () => {
    it("should display a loading state", () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([]));

        const wrapper = shallowMount(HistoryVersions, {
            localVue,
            propsData: {
                item: { id: 42 } as ItemFile,
            },
        });

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display an empty state", async () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([]));

        const wrapper = shallowMount(HistoryVersions, {
            localVue,
            propsData: {
                item: { id: 42 } as ItemFile,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display an error state", async () => {
        getAllFileVersionHistory.mockReturnValue(errAsync(Error("You cannot!")));

        const wrapper = shallowMount(HistoryVersions, {
            localVue,
            propsData: {
                item: { id: 42 } as ItemFile,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display content", async () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([{} as FileHistory]));

        const wrapper = shallowMount(HistoryVersions, {
            localVue,
            propsData: {
                item: { id: 42 } as ItemFile,
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(true);
    });
});
