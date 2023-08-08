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
import type { ArtifactField, DryRunStateType } from "../store/types";

import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";
import { TYPE_FULLY_MIGRATED, TYPE_NOT_MIGRATED, TYPE_PARTIALLY_MIGRATED } from "../store/types";

const getFields = (count: number): ArtifactField[] => {
    const fields: ArtifactField[] = [];

    for (let index = 0; index < count; index++) {
        const field_id = index + 1;

        fields.push({
            field_id,
            label: `Field ${field_id}`,
            name: `field_${field_id}`,
        });
    }

    return fields;
};

const getWrapper = (fields: ArtifactField[], type: DryRunStateType): VueWrapper =>
    shallowMount(FieldsListDisplayer, {
        global: {
            ...getGlobalTestOptions(),
        },
        props: {
            fields,
            type,
        },
    });

describe("FieldsListDisplayer", () => {
    it("should only display the list of fields if there are 5 of them or less", () => {
        const wrapper = getWrapper(getFields(5), "fully-migrated");

        expect(wrapper.findAll("[data-test=field-label]")).toHaveLength(5);
        expect(wrapper.find("[data-test=show-more-fields-button]").exists()).toBe(false);
    });

    it("When there are more than 5 fields, then it should display only 5 fields and a [Show more] button", () => {
        const wrapper = getWrapper(getFields(10), "fully-migrated");

        expect(wrapper.findAll("[data-test=field-label]")).toHaveLength(5);
        expect(wrapper.find("[data-test=show-more-fields-button]").exists()).toBe(true);
    });

    it.each([[TYPE_FULLY_MIGRATED], [TYPE_PARTIALLY_MIGRATED], [TYPE_NOT_MIGRATED]])(
        "When the type of the fields %s, then [Show more] button classes should adapt",
        (type) => {
            const wrapper = getWrapper(getFields(6), type);

            expect(wrapper.find("[data-test=show-more-fields-button]").classes()).toContain(
                `move-artifact-display-more-field-${type}`
            );
        }
    );

    it("When the user clicks on [Show more], then all the fields are shown and the button disappears", async () => {
        const wrapper = getWrapper(getFields(10), "fully-migrated");

        const show_more_button = wrapper.find("[data-test=show-more-fields-button]");

        expect(wrapper.findAll("[data-test=field-label]")).toHaveLength(5);
        expect(show_more_button.exists()).toBe(true);

        await show_more_button.trigger("click");

        expect(wrapper.findAll("[data-test=field-label]")).toHaveLength(10);
        expect(wrapper.find("[data-test=show-more-fields-button]").exists()).toBe(false);
    });
});
