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
import type {
    BaseFieldStructure,
    SemanticsRepresentation,
} from "@tuleap/plugin-tracker-rest-api-types";
import ListOfLabelDecorators from "./ListOfLabelDecorators.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { TRACKER_SEMANTICS } from "../../injection-symbols";
import { getTrackerSemantics } from "../../helpers/get-tracker-semantics";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LabelDecorator from "./LabelDecorator.vue";

describe("ListOfLabelDecorators", () => {
    const getWrapper = (
        field: Partial<BaseFieldStructure>,
        semantics: SemanticsRepresentation,
    ): VueWrapper =>
        shallowMount(ListOfLabelDecorators, {
            props: {
                field: {
                    field_id: 123,
                    name: "summary",
                    label: "Summary",
                    has_notifications: false,
                    required: false,
                    ...field,
                } as BaseFieldStructure,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [TRACKER_SEMANTICS.valueOf()]: getTrackerSemantics(semantics),
                },
            },
        });

    it("should display no label if no decorator for field", () => {
        const wrapper = getWrapper({ field_id: 123, has_notifications: false }, {});

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(0);
    });

    it("should display title decorator if field has title semantic", () => {
        const wrapper = getWrapper(
            { field_id: 123, has_notifications: false },
            {
                title: {
                    field_id: 123,
                },
            },
        );

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(1);
    });

    it("should display notification decorator if field has notifications enabled", () => {
        const wrapper = getWrapper({ field_id: 123, has_notifications: true }, {});

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(1);
    });

    it("should display title and description decorator if field has title and description semantics", () => {
        const wrapper = getWrapper(
            { field_id: 123, has_notifications: false },
            {
                title: {
                    field_id: 123,
                },
                description: {
                    field_id: 123,
                },
            },
        );

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(2);
    });

    it("should display title and notification decorator if field has title semantic and has notifications enabled", () => {
        const wrapper = getWrapper(
            { field_id: 123, has_notifications: true },
            {
                title: {
                    field_id: 123,
                },
            },
        );

        expect(wrapper.findAllComponents(LabelDecorator)).toHaveLength(2);
    });
});
