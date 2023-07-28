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

import type { Wrapper } from "@vue/test-utils";
import type { State } from "../store/types";

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createMoveModalLocalVue } from "../../tests/local-vue-for-tests";
import DryRunNotMigratedFieldState from "./DryRunNotMigratedFieldState.vue";
import FieldErrorMessage from "./FieldErrorMessage.vue";

const getWrapper = async (state: State): Promise<Wrapper<DryRunNotMigratedFieldState>> => {
    const store_options = {
        state: state,
        getters: {
            not_migrated_fields_count: state.dry_run_fields.fields_not_migrated.length,
        },
    };

    return shallowMount(DryRunNotMigratedFieldState, {
        localVue: await createMoveModalLocalVue(),
        mocks: {
            $store: createStoreMock(store_options),
        },
    });
};

const field_not_migrated = {
    field_id: 123,
    label: "A field",
    name: "a_field",
};

describe("DryRunNotMigratedFieldState", () => {
    it("should not display an error when there is no field which cannot be moved, and the move action is possible", async () => {
        const wrapper = await getWrapper({
            dry_run_fields: {
                fields_not_migrated: [],
            },
            is_move_possible: true,
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(false);
    });

    it("should display the list of fields which cannot be moved", async () => {
        const wrapper = await getWrapper({
            dry_run_fields: {
                fields_not_migrated: [field_not_migrated],
            },
            is_move_possible: true,
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(true);
        expect(wrapper.find("[data-test=not-migrated-field-error-message]").exists()).toBe(true);
        expect(wrapper.findComponent(FieldErrorMessage).exists()).toBe(true);
        expect(wrapper.find("[data-test=move-action-not-possible-error-message]").exists()).toBe(
            false
        );
    });

    it('should display the "move action not possible" error', async () => {
        const wrapper = await getWrapper({
            dry_run_fields: {
                fields_not_migrated: [field_not_migrated],
            },
            is_move_possible: false,
        });

        expect(wrapper.find("[data-test=dry-run-message-error]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-action-not-possible-error-message]").exists()).toBe(
            true
        );
        expect(wrapper.find("[data-test=not-migrated-field-error-message]").exists()).toBe(false);
        expect(wrapper.findComponent(FieldErrorMessage).exists()).toBe(false);
    });
});
