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
import type { ArtifactField } from "../store/types";

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createMoveModalLocalVue } from "../../tests/local-vue-for-tests";
import DryRunFullyMigratedFieldState from "./DryRunFullyMigratedFieldState.vue";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

const getWrapper = async (
    fields_migrated: ArtifactField[]
): Promise<Wrapper<DryRunFullyMigratedFieldState>> =>
    shallowMount(DryRunFullyMigratedFieldState, {
        localVue: await createMoveModalLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    dry_run_fields: {
                        fields_migrated,
                    },
                },
                getters: {
                    fully_migrated_fields_count: fields_migrated.length,
                },
            }),
        },
    });

describe("DryRunFullyMigratedFieldState", () => {
    it("should not display anything when there are no fully migrated fields.", async () => {
        const wrapper = await getWrapper([]);

        expect(wrapper.find("[data-test=dry-run-message-info]").exists()).toBe(false);
    });

    it("should display the list of the fully migrated fields when there are some.", async () => {
        const wrapper = await getWrapper([
            {
                field_id: 123,
                label: "A field",
                name: "a_field",
            },
        ]);

        expect(wrapper.find("[data-test=dry-run-message-info]").exists()).toBe(true);
        expect(wrapper.findComponent(FieldsListDisplayer).exists()).toBe(true);
    });
});
