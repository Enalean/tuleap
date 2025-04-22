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

import type { Card, ColumnDefinition, MappedListValue, Swimlane } from "../../../../../type";
import type { RootState } from "../../../../../store/type";
import AddCard from "../Card/Add/AddCard.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import DropContainerCell from "./DropContainerCell.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ChildCard from "../Card/ChildCard.vue";
import CardSkeleton from "../Skeleton/CardSkeleton.vue";

describe("DropContainerCell", () => {
    const mock_pointer_enter_columns = jest.fn();
    const mock_pointer_leaves_columns = jest.fn();
    const mock_expand_columns = jest.fn();

    let card = {} as Card;
    let column: ColumnDefinition = {} as ColumnDefinition;

    function getWrapper(
        column: ColumnDefinition,
        can_add_in_place: boolean,
        cards_in_cell: Card[],
        is_loading_children_cards: boolean,
        is_solo_card: boolean,
        is_in_edit_mode: boolean,
    ): VueWrapper<InstanceType<typeof DropContainerCell>> {
        const swimlane = {
            card: { ...card, is_in_edit_mode },
            is_loading_children_cards,
        } as Swimlane;

        return shallowMount(DropContainerCell, {
            props: {
                column,
                swimlane,
                is_solo_card,
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        card_being_dragged: null,
                    } as RootState,
                    getters: {
                        can_add_in_place: () => () => can_add_in_place,
                    },
                    modules: {
                        column: {
                            getters: {
                                accepted_trackers_ids: () => (): number[] => [],
                            },
                            mutations: {
                                pointerEntersColumn: mock_pointer_enter_columns,
                                pointerLeavesColumn: mock_pointer_leaves_columns,
                            },
                            actions: {
                                expandColumn: mock_expand_columns,
                            },
                            namespaced: true,
                        },
                        swimlane: {
                            getters: {
                                is_there_at_least_one_children_to_display: () => (): boolean =>
                                    true,
                                cards_in_cell: () => (): Card[] => cards_in_cell,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        card = {
            id: 1,
            mapped_list_value: { id: 103 } as MappedListValue,
            tracker_id: 88,
            is_in_edit_mode: false,
        } as Card;

        column = {
            is_collapsed: true,
            mappings: [
                {
                    tracker_id: card.tracker_id,
                    field_id: 2,
                    accepts: [{ id: card.mapped_list_value?.id }],
                },
            ],
        } as ColumnDefinition;
    });

    it(`Given the column is expanded, it displays the content of the cell`, () => {
        column.is_collapsed = false;
        const wrapper = getWrapper(column, false, [], false, true, false);

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(false);
        expect(wrapper.find("[data-test=card-with-remaining-effort]").exists()).toBe(true);
    });

    it(`Given the column is collapsed, it does not display the content of the cell`, () => {
        column.is_collapsed = true;
        const wrapper = getWrapper(column, false, [], false, false, false);

        expect(wrapper.classes("taskboard-cell-collapsed")).toBe(true);
        expect(wrapper.find("[data-test=card-with-remaining-effort]").exists()).toBe(false);
    });

    it(`when the swimlane is loading children cards,
        and there isn't any card yet,
        it displays many skeletons`, () => {
        column.is_collapsed = false;
        const wrapper = getWrapper(column, false, [], true, false, false);

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(0);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(4);
    });

    it(`when the swimlane has not yet finished to load children cards,
        it displays card of the column and one skeleton`, () => {
        column.is_collapsed = false;
        const wrapper = getWrapper(
            column,
            false,
            [{ id: 10, label: "Card 1" } as Card, { id: 22, label: "Card 2" } as Card],
            true,
            false,
            false,
        );

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(2);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(1);
    });

    it(`when the swimlane has loaded children cards,
        it displays card of the column and no skeleton`, () => {
        column.is_collapsed = false;
        const wrapper = getWrapper(
            column,
            false,
            [{ id: 1, label: "Card 1" } as Card],
            false,
            false,
            false,
        );

        expect(wrapper.findAllComponents(ChildCard)).toHaveLength(1);
        expect(wrapper.findAllComponents(CardSkeleton)).toHaveLength(0);
    });

    describe("is draggable", () => {
        let done_column: ColumnDefinition;

        beforeEach(() => {
            done_column = {
                id: 3,
                label: "Done",
                is_collapsed: false,
                mappings: [
                    {
                        tracker_id: card.tracker_id,
                        field_id: 2,
                        accepts: [{ id: card.mapped_list_value?.id }],
                    },
                ],
            } as ColumnDefinition;
        });

        it("is draggable when the card is not in edit mode", () => {
            const wrapper = getWrapper(done_column, false, [], false, true, false);

            const solo_card = wrapper.find("[data-test=card-with-remaining-effort]");

            expect(solo_card.classes()).toContain("taskboard-draggable-item");
            expect(solo_card.attributes("draggable")).toBe("true");
        });

        it("is not draggable when the card is in edit mode", () => {
            const wrapper = getWrapper(done_column, true, [], false, true, true);

            const solo_card = wrapper.find("[data-test=card-with-remaining-effort]");

            expect(solo_card.classes()).not.toContain("taskboard-draggable-item");
            expect(solo_card.attributes("draggable")).toBe("false");
        });
    });

    it(`informs the pointerenter`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false, [], false, false, false);

        wrapper.trigger("pointerenter");
        expect(mock_pointer_enter_columns).toHaveBeenCalled();
    });

    it(`informs the pointerleave`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false, [], false, false, false);

        wrapper.trigger("pointerleave");
        expect(mock_pointer_leaves_columns).toHaveBeenCalled();
    });

    it(`expands the column when user clicks on the collapsed column cell`, () => {
        const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;
        const wrapper = getWrapper(column, false, [], false, false, false);

        wrapper.trigger("click");
        expect(mock_expand_columns).toHaveBeenCalled();
    });

    describe("renders the AddCard component only when it is possible", () => {
        it(`renders the button when the tracker of the swimlane allows to add cards in place`, () => {
            const column = { is_collapsed: false } as ColumnDefinition;
            const wrapper = getWrapper(column, true, [], false, false, false);

            expect(wrapper.findComponent(AddCard).exists()).toBe(true);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(true);
        });

        it(`does not render the AddCard component
            when the tracker of the swimlane disallows to add cards in place`, () => {
            const column = { is_collapsed: false } as ColumnDefinition;
            const wrapper = getWrapper(column, false, [], false, false, false);

            expect(wrapper.findComponent(AddCard).exists()).toBe(false);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(false);
        });

        it(`does not render the AddCard component when the column is collapsed`, () => {
            const column = { is_collapsed: true } as ColumnDefinition;
            const wrapper = getWrapper(column, true, [], false, false, false);

            expect(wrapper.findComponent(AddCard).exists()).toBe(false);
            expect(wrapper.classes("taskboard-cell-with-add-form")).toBe(false);
        });
    });
});
