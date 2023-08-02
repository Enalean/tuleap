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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FileVersionHistory from "./FileVersionHistory.vue";
import { TYPE_FILE } from "../../../../../constants";
import * as version_history_retriever from "../../../../../helpers/version-history-retriever";
import type { FileHistory } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../../../store/configuration";
import { nextTick } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("FileVersionHistory", () => {
    function createWrapper(
        has_error = false,
        is_filename_pattern_enforced = true,
        is_loading = false
    ): VueWrapper<InstanceType<typeof FileVersionHistory>> {
        return shallowMount(FileVersionHistory, {
            props: { item: { id: 18, type: TYPE_FILE } },
            data() {
                return {
                    has_error,
                    error_message: "Some error",
                    is_loading,
                };
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                is_filename_pattern_enforced,
                                project_id: 25,
                            } as unknown as ConfigurationState,
                            namespaced: true,
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
                number: 10,
                name: "Version 1",
                filename: "huhuhoho.pdf",
                download_href: "https://example.com/huhuhoho.pdf",
            },
            {
                number: 35,
                name: "wololo",
                filename: "convert.pdf",
                download_href: "https://example.com/convert.pdf",
            },
        ] as FileHistory[];
        version_history_spy.mockResolvedValue(versions);

        const wrapper = createWrapper();

        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.vm.versions).toHaveLength(2);
        expect(wrapper.vm.are_versions_loading).toBe(false);
    });

    it("displays the latest version text and the file history link", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        const versions = [
            {
                number: 10,
                name: "Version 1",
                filename: "huhuhoho.pdf",
                download_href: "https://example.com/huhuhoho.pdf",
            },
            {
                number: 35,
                name: "wololo",
                filename: "convert.pdf",
                download_href: "https://example.com/convert.pdf",
            },
            {
                number: 45,
                name: "hearts",
                filename: "coeurs.pdf",
                download_href: "https://example.com/coeurs.pdf",
            },
            {
                number: 18,
                name: "naha",
                filename: "city.pdf",
                download_href: "https://example.com/convert.pdf",
            },
            {
                number: 19,
                name: "Supra",
                filename: "2jz.pdf",
                download_href: "https://example.com/convert.pdf",
            },
        ] as FileHistory[];
        version_history_spy.mockResolvedValue(versions);

        const wrapper = createWrapper();

        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.vm.versions).toHaveLength(5);
        expect(wrapper.vm.are_versions_loading).toBe(false);
    });

    it("displays the empty message when there is no version", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockResolvedValue([] as ReadonlyArray<FileHistory>);
        const wrapper = createWrapper();

        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.vm.versions).toHaveLength(0);
        expect(wrapper.vm.are_versions_loading).toBe(false);
        expect(wrapper.vm.is_version_history_empty).toBe(true);
    });

    it("displays the error if an error occurred when retrieving the history version", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockRejectedValue(
            new FetchWrapperError("Lorem ipsum", {
                json: () =>
                    Promise.resolve({
                        error: {
                            code: 400,
                            message: "Bad request",
                            i18n_error_message: "Something goes wrong",
                        },
                    }),
            } as Response)
        );
        const wrapper = createWrapper(true);

        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.vm.versions).toHaveLength(0);
        expect(wrapper.vm.get_has_error).toBe(true);
    });

    it("displays nothing if when filename pattern is not enabled", async () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        const is_filename_pattern_enforced = false;

        const wrapper = createWrapper(false, is_filename_pattern_enforced);

        await nextTick();
        await nextTick();

        expect(version_history_spy).not.toHaveBeenCalled();
        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it("displays the skeleton during the loading of the history", () => {
        const version_history_spy = jest.spyOn(version_history_retriever, "getVersionHistory");
        version_history_spy.mockResolvedValue([] as ReadonlyArray<FileHistory>);

        const is_loading = true;
        const wrapper = createWrapper(false, true, is_loading);

        expect(wrapper.vm.versions).toHaveLength(0);
        expect(wrapper.vm.are_versions_loading).toBe(true);
    });
});
