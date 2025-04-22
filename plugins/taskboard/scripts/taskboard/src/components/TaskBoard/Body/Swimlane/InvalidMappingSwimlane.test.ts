/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import type { ColumnDefinition, Swimlane } from "../../../../type";
import InvalidMappingSwimlane from "./InvalidMappingSwimlane.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import ParentCell from "./ParentCell.vue";
import InvalidMappingCell from "./Cell/InvalidMappingCell.vue";

function createWrapper(
    columns: ColumnDefinition[],
    swimlane: Swimlane,
): VueWrapper<InstanceType<typeof InvalidMappingSwimlane>> {
    return shallowMount(InvalidMappingSwimlane, {
        global: {
            ...getGlobalTestOptions({
                modules: {
                    column: {
                        state: {
                            columns: [columns],
                        },
                        namespaced: true,
                    },
                },
            }),
        },
        props: { swimlane },
    });
}

describe(`InvalidMappingSwimlane`, () => {
    it("displays the parent card in its own cell when status does not map to a column", () => {
        const columns = [
            { id: 2, label: "To do" } as ColumnDefinition,
            { id: 3, label: "Done" } as ColumnDefinition,
        ];
        const swimlane = { card: { id: 43, mapped_list_value: null } } as Swimlane;

        const wrapper = createWrapper(columns, swimlane);

        expect(wrapper.findComponent(ParentCell).exists()).toBe(true);
        expect(wrapper.findComponent(InvalidMappingCell).exists()).toBe(true);
    });
});
