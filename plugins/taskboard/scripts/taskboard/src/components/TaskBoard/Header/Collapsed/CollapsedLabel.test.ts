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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CollapsedLabel from "./CollapsedLabel.vue";
import type { ColumnDefinition } from "../../../../type";
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";

function getWrapper(column: ColumnDefinition): VueWrapper<InstanceType<typeof CardsInColumnCount>> {
    return shallowMount(CollapsedLabel, {
        props: { column },
    });
}

describe("CollapsedLabel", () => {
    it(`displays the label of the column`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        expect(wrapper.text()).toBe("Done");
    });

    it(`displays the number of cards in column`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        expect(wrapper.findComponent(CardsInColumnCount).exists()).toBe(true);
    });
});
