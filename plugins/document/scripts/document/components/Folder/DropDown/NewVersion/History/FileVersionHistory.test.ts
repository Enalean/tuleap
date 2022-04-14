/*
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../../helpers/local-vue";
import FileVersionHistory from "./FileVersionHistory.vue";
import FileVersionHistoryContent from "./FileVersionHistoryContent.vue";
import { TYPE_FILE } from "../../../../../constants";
import * as version_history_retriever from "../../../../../helpers/version-history-retriever";
import type { FileHistory } from "../../../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import FileVersionHistorySkeleton from "./FileVersionHistorySkeleton.vue";

describe("FileVersionHistory", () => {
    function createWrapper(
        has_error = false,
        is_filename_pattern_enforced = true,
        is_loading = false
    ): Wrapper<FileVersionHistory> {
        return shallowMount(FileVersionHistory, {
            localVue,
            propsData: { item: { id: 18, type: TYPE_FILE } },
            data() {
                return {
                    has_error,
                    error_message: "Some error",
                    is_loading,
                };
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            is_filename_pattern_enforced,
                            project_id: 25,
                        },
                    },
                }),
            },
        });
    }

    it("displays the version history", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        const versions = [
            {
                id: 10,
                name: "Version 1",
                filename: "huhuhoho.pdf",
                download_href: "https://example.com/huhuhoho.pdf",
            },
            {
                id: 35,
                name: "wololo",
                filename: "convert.pdf",
                download_href: "https://example.com/convert.pdf",
            },
        ];
        version_history_spy.mockResolvedValue(versions);

        const wrapper = createWrapper();

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findAllComponents(FileVersionHistoryContent)).toHaveLength(2);
        expect(wrapper.findAllComponents(FileVersionHistorySkeleton)).toHaveLength(0);
    });

    it("displays the latest version text and the file history link", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        const versions = [
            {
                id: 10,
                name: "Version 1",
                filename: "huhuhoho.pdf",
                download_href: "https://example.com/huhuhoho.pdf",
            },
            {
                id: 35,
                name: "wololo",
                filename: "convert.pdf",
                download_href: "https://example.com/convert.pdf",
            },
            {
                id: 45,
                name: "hearts",
                filename: "coeurs.pdf",
                download_href: "https://example.com/coeurs.pdf",
            },
            {
                id: 18,
                name: "naha",
                filename: "city.pdf",
                download_href: "https://example.com/convert.pdf",
            },
            {
                id: 19,
                name: "Supra",
                filename: "2jz.pdf",
                download_href: "https://example.com/convert.pdf",
            },
        ];
        version_history_spy.mockResolvedValue(versions);

        const wrapper = createWrapper();

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findAllComponents(FileVersionHistoryContent)).toHaveLength(5);
        expect(wrapper.findAllComponents(FileVersionHistorySkeleton)).toHaveLength(0);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the empty message when there is no version", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockResolvedValue([] as ReadonlyArray<FileHistory>);
        const wrapper = createWrapper();

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findAllComponents(FileVersionHistoryContent)).toHaveLength(0);
        expect(wrapper.findAllComponents(FileVersionHistorySkeleton)).toHaveLength(0);
        expect(wrapper).toMatchSnapshot();
    });

    it("displays the error if an error occurred when retrieving the history version", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockResolvedValue([] as ReadonlyArray<FileHistory>);
        const wrapper = createWrapper(true);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findAllComponents(FileVersionHistoryContent)).toHaveLength(0);
        expect(wrapper.findAllComponents(FileVersionHistorySkeleton)).toHaveLength(0);
        expect(wrapper).toMatchSnapshot();
    });

    it("displays nothing if when filename pattern is not enabled", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        const is_filename_pattern_enforced = false;

        const wrapper = createWrapper(false, is_filename_pattern_enforced);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(version_history_spy).not.toHaveBeenCalled();
        expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
    });
    it("displays the skeleton during the loading of the history", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockResolvedValue([] as ReadonlyArray<FileHistory>);

        const is_loading = true;
        const wrapper = createWrapper(false, true, is_loading);

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findAllComponents(FileVersionHistoryContent)).toHaveLength(0);
        expect(wrapper.findAllComponents(FileVersionHistorySkeleton)).toHaveLength(5);
    });
});
