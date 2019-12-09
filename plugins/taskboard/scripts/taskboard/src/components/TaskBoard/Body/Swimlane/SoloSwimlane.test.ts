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
import SoloSwimlane from "./SoloSwimlane.vue";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { Card, ColumnDefinition, Swimlane, User } from "../../../../type";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { RootState } from "../../../../store/type";
import CardWithRemainingEffort from "./Card/CardWithRemainingEffort.vue";
import AddCard from "./Card/Add/AddCard.vue";

async function createWrapper(
    columns: ColumnDefinition[],
    target_column: ColumnDefinition,
    swimlane: Swimlane,
    can_add_in_place: boolean
): Promise<Wrapper<SoloSwimlane>> {
    return shallowMount(SoloSwimlane, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: createStoreMock({
                state: {
                    column: { columns }
                } as RootState,
                getters: {
                    "column/accepted_trackers_ids": (): number[] => [101, 102],
                    can_add_in_place: (): boolean => can_add_in_place
                }
            })
        },
        propsData: { swimlane, column: target_column }
    });
}

describe("SoloSwimlane", () => {
    it("displays the parent card in Done column when status maps this column", async () => {
        const done_column = { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition;

        const columns = [
            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
            done_column
        ];
        const swimlane = { card: { id: 43 } } as Swimlane;
        const wrapper = await createWrapper(columns, done_column, swimlane, false);

        expect(wrapper.element).toMatchSnapshot();
        expect(wrapper.contains(AddCard)).toBe(false);
    });

    it("Allows to add cards", async () => {
        const done_column = { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition;

        const columns = [
            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
            done_column
        ];
        const swimlane = { card: { id: 43 } } as Swimlane;
        const wrapper = await createWrapper(columns, done_column, swimlane, true);

        expect(wrapper.contains(AddCard)).toBe(true);
    });

    it(`Given the parent card is in Done column
        and status maps this column
        and column is collapsed
        then swimlane is not displayed at all`, async () => {
        const done_column = { id: 3, label: "Done", is_collapsed: true } as ColumnDefinition;

        const columns = [
            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
            done_column
        ];
        const swimlane = { card: { id: 43 } } as Swimlane;
        const wrapper = await createWrapper(columns, done_column, swimlane, false);

        expect(wrapper.isEmpty()).toBe(true);
    });

    describe("is draggable", () => {
        let card: Card,
            done_column: ColumnDefinition,
            columns: ColumnDefinition[],
            swimlane: Swimlane;

        beforeEach(() => {
            card = {
                id: 43,
                assignees: [] as User[],
                is_open: true,
                is_in_edit_mode: true
            } as Card;

            done_column = { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition;

            columns = [
                { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
                done_column
            ];
            swimlane = { card } as Swimlane;
        });

        it("is draggable when the card is not in edit mode", async () => {
            card.is_in_edit_mode = false;

            const wrapper = await createWrapper(columns, done_column, swimlane, false);

            const solo_card = wrapper.find(CardWithRemainingEffort);

            expect(solo_card.classes()).toContain("taskboard-draggable-item");
            expect(solo_card.attributes("data-is-draggable")).toBe("true");
        });

        it("is not draggable when the card is in edit mode", async () => {
            card.is_in_edit_mode = true;

            const wrapper = await createWrapper(columns, done_column, swimlane, false);

            const solo_card = wrapper.find(CardWithRemainingEffort);

            expect(solo_card.classes()).not.toContain("taskboard-draggable-item");
            expect(solo_card.attributes("data-is-draggable")).toBeFalsy();
        });
    });
});
