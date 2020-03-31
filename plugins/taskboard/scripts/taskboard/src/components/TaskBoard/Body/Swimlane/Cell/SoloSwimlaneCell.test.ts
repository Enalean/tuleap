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

import { Card, ColumnDefinition, Swimlane, User } from "../../../../../type";
import { shallowMount, Wrapper } from "@vue/test-utils";
import SoloSwimlaneCell from "./SoloSwimlaneCell.vue";
import CardWithRemainingEffort from "../Card/CardWithRemainingEffort.vue";

function createWrapper(column: ColumnDefinition, swimlane: Swimlane): Wrapper<SoloSwimlaneCell> {
    return shallowMount(SoloSwimlaneCell, {
        propsData: { column, swimlane },
    });
}

describe(`SoloSwimlaneCell`, () => {
    it("displays the solo card in Done column when status maps this column", () => {
        const done_column = {
            id: 3,
            label: "Done",
            is_collapsed: false,
            mappings: [{ accepts: [{ id: 103 }, { id: 104 }] }],
        } as ColumnDefinition;

        const swimlane = { card: { id: 43, mapped_list_value: { id: 103 } } } as Swimlane;
        const wrapper = createWrapper(done_column, swimlane);

        expect(wrapper.contains(CardWithRemainingEffort)).toBe(true);
    });

    describe("is draggable", () => {
        let card: Card, done_column: ColumnDefinition, swimlane: Swimlane;

        beforeEach(() => {
            card = {
                id: 43,
                assignees: [] as User[],
                is_open: true,
                is_in_edit_mode: true,
                mapped_list_value: { id: 103 },
            } as Card;

            done_column = {
                id: 3,
                label: "Done",
                is_collapsed: false,
                mappings: [{ accepts: [{ id: 103 }] }],
            } as ColumnDefinition;

            swimlane = { card } as Swimlane;
        });

        it("is draggable when the card is not in edit mode", () => {
            card.is_in_edit_mode = false;

            const wrapper = createWrapper(done_column, swimlane);

            const solo_card = wrapper.get(CardWithRemainingEffort);

            expect(solo_card.classes()).toContain("taskboard-draggable-item");
            expect(solo_card.attributes("draggable")).toBe("true");
        });

        it("is not draggable when the card is in edit mode", () => {
            card.is_in_edit_mode = true;

            const wrapper = createWrapper(done_column, swimlane);

            const solo_card = wrapper.get(CardWithRemainingEffort);

            expect(solo_card.classes()).not.toContain("taskboard-draggable-item");
            expect(solo_card.attributes("draggable")).toBeFalsy();
        });
    });
});
