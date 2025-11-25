/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import FieldsUsage from "./FieldsUsage.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import LoadingState from "./LoadingState.vue";
import ErrorState from "./ErrorState.vue";
import EmptyState from "./EmptyState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import process from "node:process";

describe("FieldsUsage", () => {
    it("should display a loading state", () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        expect(wrapper.findComponent(LoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(false);
    });

    it("should display an error state", async () => {
        const getJSON = vi.spyOn(fetch_result, "getJSON");

        getJSON.mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));

        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        await new Promise(process.nextTick);

        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(false);
    });

    it("should display an empty state", async () => {
        const getJSON = vi.spyOn(fetch_result, "getJSON");

        getJSON.mockReturnValue(okAsync({ fields: [], structure: [] }));

        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        await new Promise(process.nextTick);

        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(false);
    });

    it("should display fields", async () => {
        const getJSON = vi.spyOn(fetch_result, "getJSON");

        getJSON.mockReturnValue(
            okAsync({ fields: [{ field_id: 123 }], structure: [{ id: 123, content: null }] }),
        );

        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        await new Promise(process.nextTick);

        expect(wrapper.findComponent(LoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(true);
    });
});
