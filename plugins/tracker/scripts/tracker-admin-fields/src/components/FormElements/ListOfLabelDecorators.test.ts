/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import type { BaseFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import ListOfLabelDecorators from "./ListOfLabelDecorators.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LabelDecorator from "./LabelDecorator.vue";

describe("ListOfLabelDecorators", () => {
    const getWrapper = (field: Partial<BaseFieldStructure>): VueWrapper =>
        shallowMount(ListOfLabelDecorators, {
            props: {
                field: {
                    field_id: 123,
                    name: "summary",
                    label: "Summary",
                    has_notifications: false,
                    required: false,
                    label_decorators: [],
                    ...field,
                } as BaseFieldStructure,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });

    it("should display no label if no decorator for field", () => {
        const wrapper = getWrapper({
            field_id: 123,
            label_decorators: [],
        });

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(0);
    });

    it("should display title decorator if field has title semantic", () => {
        const wrapper = getWrapper({
            field_id: 123,
            label_decorators: [
                {
                    label: "Title",
                    description: "The title",
                },
            ],
        });

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(1);
    });

    it("should display title and description decorators", () => {
        const wrapper = getWrapper({
            field_id: 123,
            label_decorators: [
                {
                    label: "Title",
                    description: "The title",
                },
                {
                    label: "Description",
                    description: "The description",
                },
            ],
        });

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(2);
    });
});
