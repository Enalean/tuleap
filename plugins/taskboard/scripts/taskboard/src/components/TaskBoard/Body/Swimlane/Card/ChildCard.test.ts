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
import ChildCard from "./ChildCard.vue";
import { Card, User } from "../../../../../type";
import { createStoreMock } from "../../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import BaseCard from "./BaseCard.vue";

function getWrapper(card: Card, are_closed_items_displayed: boolean): Wrapper<ChildCard> {
    return shallowMount(ChildCard, {
        propsData: {
            card,
        },
        mocks: {
            $store: createStoreMock({
                state: {
                    are_closed_items_displayed,
                },
            }),
        },
    });
}

describe("ChildCard", () => {
    describe("Closed items", () => {
        it(`Given user does not want to see closed items
        When the card is closed
        Then it is not rendered`, () => {
            const card: Card = {
                id: 43,
                assignees: [] as User[],
                is_open: false,
            } as Card;

            const wrapper = getWrapper(card, false);

            expect(wrapper.isEmpty()).toBe(true);
        });

        it(`Given user wants to see closed items
        When the card is closed
        Then it is rendered`, () => {
            const card: Card = {
                id: 43,
                assignees: [] as User[],
                is_open: false,
            } as Card;

            const wrapper = getWrapper(card, true);

            expect(wrapper.isEmpty()).toBe(false);
            expect(wrapper.get(BaseCard).props("card")).toBe(card);
        });

        it(`adds draggable attributes`, () => {
            const card: Card = {
                id: 43,
                tracker_id: 69,
                assignees: [] as User[],
                is_open: false,
            } as Card;

            const wrapper = getWrapper(card, true);

            expect(wrapper.attributes("data-card-id")).toBe("43");
            expect(wrapper.attributes("data-tracker-id")).toBe("69");
            expect(wrapper.classes("taskboard-draggable-item")).toBe(true);
        });
    });

    describe("is draggable", () => {
        it("is draggable when the card is not in edit mode", () => {
            const card: Card = {
                id: 43,
                assignees: [] as User[],
                is_open: true,
                is_in_edit_mode: false,
            } as Card;

            const wrapper = getWrapper(card, true);

            expect(wrapper.classes()).toContain("taskboard-draggable-item");
            expect(wrapper.attributes("draggable")).toBe("true");
        });

        it("is not draggable when the card is in edit mode", () => {
            const card: Card = {
                id: 43,
                assignees: [] as User[],
                is_open: true,
                is_in_edit_mode: true,
            } as Card;

            const wrapper = getWrapper(card, true);

            expect(wrapper.classes()).not.toContain("taskboard-draggable-item");
            expect(wrapper.attributes("draggable")).toBe("false");
        });
    });
});
