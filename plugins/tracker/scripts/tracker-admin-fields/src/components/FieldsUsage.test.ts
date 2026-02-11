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
import EmptyState from "./EmptyState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import ErrorState from "./ErrorState.vue";
import { FIELDS } from "../injection-symbols";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));

vi.useFakeTimers();

describe("FieldsUsage", () => {
    it("should display an empty state", async () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                structure: [],
                has_error: false,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [FIELDS.valueOf()]: [],
                },
            },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(ErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(false);
    });

    it("should display fields", async () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                structure: [{ id: 123, content: null }],
                has_error: false,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [FIELDS.valueOf()]: [
                        {
                            field_id: 123,
                            name: "details",
                            label: "Details",
                            type: CONTAINER_FIELDSET,
                            required: false,
                            has_notifications: false,
                            label_decorators: [],
                        },
                    ],
                },
            },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(ErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(true);
    });
    it("should display and error", async () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                structure: [{ id: 123, content: null }],
                has_error: true,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [FIELDS.valueOf()]: [
                        {
                            field_id: 123,
                            name: "details",
                            label: "Details",
                            type: CONTAINER_FIELDSET,
                            required: false,
                            has_notifications: false,
                            label_decorators: [],
                        },
                    ],
                },
            },
        });

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(ErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(true);
    });
});
