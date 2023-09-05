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
import DryRunNotMigratedFieldState from "./DryRunNotMigratedFieldState.vue";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

const getWrapper = (): VueWrapper =>
    shallowMount(DryRunNotMigratedFieldState, {
        global: {
            ...getGlobalTestOptions(),
        },
    });

const field = {
    field_id: 123,
    label: "A field",
    name: "a_field",
};

describe("DryRunNotMigratedFieldState", () => {
    it("should not display an error when there is no field which cannot be moved, and the move action is possible", async () => {
        const wrapper = getWrapper();

        await useDryRunStore().$patch({
            fields_not_migrated: [],
            fields_partially_migrated: [],
            fields_migrated: [field],
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(false);
    });

    it("should display the list of fields which cannot be moved", async () => {
        const wrapper = getWrapper();

        await useDryRunStore().$patch({
            fields_not_migrated: [field],
            fields_partially_migrated: [],
            fields_migrated: [field],
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(true);
        expect(wrapper.find("[data-test=not-migrated-field-error-message]").exists()).toBe(true);
        expect(wrapper.findComponent(FieldsListDisplayer).exists()).toBe(true);
        expect(wrapper.find("[data-test=move-action-not-possible-error-message]").exists()).toBe(
            false,
        );
    });

    it('should display the "move action not possible" error', async () => {
        const wrapper = getWrapper();

        await useDryRunStore().$patch({
            fields_not_migrated: [field],
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-action-not-possible-error-message]").exists()).toBe(
            true,
        );
        expect(wrapper.find("[data-test=not-migrated-field-error-message]").exists()).toBe(false);
        expect(wrapper.findComponent(FieldsListDisplayer).exists()).toBe(false);
    });
});
