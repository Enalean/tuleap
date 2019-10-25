/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import CellForSoloCard from "./CellForSoloCard.vue";
import { ColumnDefinition } from "../../../../type";

describe("CellForSoloCard", () => {
    it(`Given the column is expanded, it displays the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;
        const wrapper = shallowMount(CellForSoloCard, {
            propsData: { column },
            slots: {
                default: '<div class="my-slot-content"></div>'
            }
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(false);
        expect(wrapper.contains(".my-slot-content")).toBe(true);
    });

    it(`Given the column is collapsed, it does not display the content of the cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = shallowMount(CellForSoloCard, {
            propsData: { column },
            slots: {
                default: '<div class="my-slot-content"></div>'
            }
        });

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
        expect(wrapper.contains(".my-slot-content")).toBe(false);
    });
});
