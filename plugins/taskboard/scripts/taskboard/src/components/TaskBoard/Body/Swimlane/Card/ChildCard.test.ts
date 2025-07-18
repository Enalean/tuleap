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
import ChildCard from "./ChildCard.vue";
import type { Card, User } from "../../../../../type";
import BaseCard from "./BaseCard.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { RootState } from "../../../../../store/type";

function getWrapper(
    card: Card,
    are_closed_items_displayed: boolean,
): VueWrapper<InstanceType<typeof ChildCard>> {
    return shallowMount(ChildCard, {
        attachTo: document.body,
        props: {
            card,
        },
        global: {
            ...getGlobalTestOptions({
                state: {
                    are_closed_items_displayed,
                } as RootState,
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

            expect(wrapper.html()).toBe("<!--v-if-->");
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

            expect(wrapper.html()).not.toBe("");
            expect(wrapper.findComponent(BaseCard).props("card")).toStrictEqual(card);
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

    it("focuses the card when receiving the `editor-closed` event", () => {
        const card: Card = {
            id: 43,
            assignees: [] as User[],
            is_open: false,
        } as Card;
        const wrapper = getWrapper(card, true);

        const base_card = wrapper.findComponent(BaseCard);
        base_card.vm.$emit("editor-closed");

        if (!(document.activeElement instanceof HTMLElement)) {
            throw new Error("Active element should be the ChildCard element");
        }
        expect(document.activeElement.dataset.test).toBe("child-card");
    });
});
