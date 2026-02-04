/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { shallowMount, flushPromises } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import * as router from "vue-router";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import { createGettext } from "vue3-gettext";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import * as tuleap_api from "../api/rest-querier";
import ChangesPane from "./ChangesPane.vue";
import type { PullRequestFile } from "../api/rest-querier";
import { FILE_STATUS_ADDED, FILE_STATUS_MODIFIED } from "../api/rest-querier";
import FilesSelector from "./files-selector/FilesSelector.vue";
import AppSkeleton from "./AppSkeleton.vue";

vi.mock("vue-router");

const pull_request_id = 12;
const file_path = "src/main.ts";
const changes: PullRequestFile[] = [
    {
        path: "README.md",
        status: FILE_STATUS_MODIFIED,
        lines_removed: Option.fromValue(2),
        lines_added: Option.fromValue(2),
    },
    {
        path: file_path,
        status: FILE_STATUS_ADDED,
        lines_removed: Option.fromValue(47),
        lines_added: Option.nothing(),
    },
    {
        path: "pnpm-lock.yml",
        status: FILE_STATUS_MODIFIED,
        lines_removed: Option.nothing(),
        lines_added: Option.fromValue(7569453418643748),
    },
];

describe("ChangesPane", () => {
    const getWrapper = (): VueWrapper =>
        shallowMount(ChangesPane, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

    beforeEach(() => {
        vi.spyOn(router, "useRoute").mockImplementationOnce(
            () =>
                ({
                    params: {
                        id: String(pull_request_id),
                        file_path: encodeURIComponent(file_path),
                    },
                }) as unknown as RouteLocationNormalizedLoaded,
        );
    });

    it("should load the pull-request's changes", async () => {
        vi.spyOn(tuleap_api, "getFiles").mockReturnValue(okAsync(changes));

        const wrapper = getWrapper();

        expect(wrapper.findComponent(AppSkeleton).exists()).toBe(true);

        await flushPromises();

        expect(wrapper.findComponent(FilesSelector).exists()).toBe(true);
        expect(wrapper.find("[data-test=no-changes-warning]").exists()).toBe(false);
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
    });

    it("should display a warning when the pull-request has no commit", async () => {
        vi.spyOn(tuleap_api, "getFiles").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await flushPromises();

        expect(wrapper.findComponent(FilesSelector).exists()).toBe(false);
        expect(wrapper.find("[data-test=no-changes-warning]").exists()).toBe(true);
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
    });

    it("should display an error when the loading of the changes has failed", async () => {
        vi.spyOn(tuleap_api, "getFiles").mockReturnValue(errAsync(Fault.fromMessage("Nope")));

        const wrapper = getWrapper();
        await flushPromises();

        expect(wrapper.findComponent(FilesSelector).exists()).toBe(false);
        expect(wrapper.find("[data-test=no-changes-warning]").exists()).toBe(false);
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(true);
    });
});
