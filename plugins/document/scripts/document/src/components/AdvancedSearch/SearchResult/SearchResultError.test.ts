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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import SearchResultError from "./SearchResultError.vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

vi.useFakeTimers();

describe("SearchResultError", () => {
    it("should display error message", () => {
        const wrapper = shallowMount(SearchResultError, {
            props: {
                error: new Error("Lorem ipsum"),
            },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(wrapper.text()).toContain("Lorem ipsum");
    });

    it("should display error message of a FetchWrapperError", async () => {
        const wrapper = shallowMount(SearchResultError, {
            props: {
                error: new FetchWrapperError("Lorem ipsum", {
                    json: () =>
                        Promise.resolve({
                            error: {
                                code: 400,
                                message: "Bad request",
                            },
                        }),
                } as Response),
            },
            global: { ...getGlobalTestOptions({}) },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.text()).toContain("400 Bad request");
    });

    it("should display i18n error message of a FetchWrapperError", async () => {
        const wrapper = shallowMount(SearchResultError, {
            props: {
                error: new FetchWrapperError("Lorem ipsum", {
                    json: () =>
                        Promise.resolve({
                            error: {
                                code: 400,
                                message: "Bad request",
                                i18n_error_message: "Les paramètres ne sont pas corrects",
                            },
                        }),
                } as Response),
            },
            global: { ...getGlobalTestOptions({}) },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.text()).toContain("Les paramètres ne sont pas corrects");
    });

    it("should default to default message of FetchWrapperError if it does not contain an error object", async () => {
        const wrapper = shallowMount(SearchResultError, {
            props: {
                error: new FetchWrapperError("Lorem ipsum", {
                    json: () => Promise.resolve({}),
                } as Response),
            },
            global: { ...getGlobalTestOptions({}) },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.text()).toContain("Lorem ipsum");
    });

    it("should default to default message of FetchWrapperError if response is malformed", async () => {
        const wrapper = shallowMount(SearchResultError, {
            props: {
                error: new FetchWrapperError("Lorem ipsum", {
                    json: () => Promise.reject(new Error("No valid json")),
                } as Response),
            },
            global: { ...getGlobalTestOptions({}) },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.text()).toContain("Lorem ipsum");
    });
});
