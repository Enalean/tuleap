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

import { shallowMount, Wrapper } from "@vue/test-utils";
import CollapsedLabel from "./CollapsedLabel.vue";
import { ColumnDefinition } from "../../../../type";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../../store/type";
import CardsInColumnCount from "../Expanded/CardsInColumnCount.vue";

function getWrapper(column: ColumnDefinition): Wrapper<CollapsedLabel> {
    return shallowMount(CollapsedLabel, {
        propsData: { column },
        mocks: { $store: createStoreMock({ state: { column: {} } as RootState }) }
    });
}

describe("CollapsedLabel", () => {
    it(`It displays the label of the column`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        expect(wrapper.text()).toBe("Done");
    });

    it(`It displays the number of cards in column`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        expect(wrapper.contains(CardsInColumnCount)).toBe(true);
    });

    it(`It informs the mouseover when the column is collapsed`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseover");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseEntersColumn", column);
    });

    it(`It does not inform the mouseover when the column is expanded`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseover");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith(
            "column/mouseEntersColumn",
            column
        );
    });

    it(`It informs the mouseout when the column is collapsed`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("column/mouseLeavesColumn", column);
    });

    it(`It does not inform the mouseout when the column is expanded`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: false } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("mouseout");
        expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith(
            "column/mouseLeavesColumn",
            column
        );
    });

    it(`it expands the column when use click on the collapsed label`, () => {
        const column: ColumnDefinition = { label: "Done", is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column);

        wrapper.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("column/expandColumn", column);
    });
});
