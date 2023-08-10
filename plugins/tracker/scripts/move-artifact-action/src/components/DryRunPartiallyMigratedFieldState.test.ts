/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";

import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { useDryRunStore } from "../stores/dry-run";
import DryRunPartiallyMigratedFieldState from "./DryRunPartiallyMigratedFieldState.vue";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

const getWrapper = (): VueWrapper =>
    shallowMount(DryRunPartiallyMigratedFieldState, {
        global: {
            ...getGlobalTestOptions(),
        },
    });

describe("DryRunPartiallyMigratedFieldState", () => {
    it("should not display anything when there are no partially migrated fields.", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=dry-run-message-warning]").exists()).toBe(false);
    });

    it("should display the list of the partially migrated fields when there are some.", async () => {
        const wrapper = getWrapper();

        await useDryRunStore().$patch({
            fields_partially_migrated: [
                {
                    field_id: 123,
                    label: "A field",
                    name: "a_field",
                },
            ],
        });

        expect(wrapper.find("[data-test=dry-run-message-warning]").exists()).toBe(true);
        expect(wrapper.findComponent(FieldsListDisplayer).exists()).toBe(true);
    });
});
