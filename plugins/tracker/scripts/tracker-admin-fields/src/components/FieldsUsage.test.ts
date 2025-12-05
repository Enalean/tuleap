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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import FieldsUsage from "./FieldsUsage.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import EmptyState from "./EmptyState.vue";
import TrackerStructure from "./TrackerStructure.vue";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";

describe("FieldsUsage", () => {
    it("should display an empty state", async () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
                fields: [],
                structure: [],
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        await new Promise(process.nextTick);

        expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(false);
    });

    it("should display fields", async () => {
        const wrapper = shallowMount(FieldsUsage, {
            props: {
                tracker_id: 123,
                fields: [
                    {
                        field_id: 123,
                        name: "details",
                        label: "Details",
                        type: CONTAINER_FIELDSET,
                        required: false,
                    },
                ],
                structure: [{ id: 123, content: null }],
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

        await new Promise(process.nextTick);

        expect(wrapper.findComponent(EmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TrackerStructure).exists()).toBe(true);
    });
});
