/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import { expect, it, describe } from "vitest";
import { shallowMount } from "@vue/test-utils";
import FieldId from "./FieldId.vue";
import { ARTIFACT_ID_FIELD, ARTIFACT_ID_IN_TRACKER_FIELD } from "@tuleap/plugin-tracker-constants";

describe("FieldId", () => {
    it.each([
        ["Per tracker Id", ARTIFACT_ID_IN_TRACKER_FIELD, true, false],
        ["Artifact id", ARTIFACT_ID_FIELD, false, true],
    ])(
        `displays the %s field`,
        (label, type, is_per_tracker_field_displayed, is_artifact_id_field_displayed) => {
            const wrapper = shallowMount(FieldId, {
                props: {
                    field: {
                        field_id: 123,
                        name: "id",
                        label,
                        type,
                        required: false,
                    },
                },
            });

            expect(wrapper.find("[data-test=artifact-id-in-tracker-field]").exists()).toBe(
                is_per_tracker_field_displayed,
            );
            expect(wrapper.find("[data-test=artifact-id-field]").exists()).toBe(
                is_artifact_id_field_displayed,
            );
        },
    );
});
