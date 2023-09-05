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

import type { Slots, Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CardInfo from "./CardInfo.vue";
import type { Card, Tracker, User } from "../../../../../type";
import CardAssignees from "./CardAssignees.vue";

function getWrapper(card: Card, slots: Slots = {}): Wrapper<CardInfo> {
    return shallowMount(CardInfo, {
        propsData: { card, tracker: {} as Tracker, value: [] as number[] },
        slots,
    });
}

function getCard(
    definition: Card = {
        is_in_edit_mode: false,
    } as Card,
): Card {
    return {
        ...definition,
        id: 43,
        color: "lake-placid-blue",
        assignees: [] as User[],
    } as Card;
}

describe("CardInfo", () => {
    it("Displays the assignees", () => {
        const wrapper = getWrapper(getCard());

        expect(wrapper.findComponent(CardAssignees).exists()).toBe(true);
    });

    it("Includes the initial effort slot", () => {
        const wrapper = getWrapper(getCard(), {
            initial_effort: '<div class="my-initial-effort"></div>',
        });

        expect(wrapper.find(".taskboard-card-info > .my-initial-effort").exists()).toBe(true);
    });

    it("Does not include the initial effort slot if card is in edit mode", () => {
        const wrapper = getWrapper(getCard({ is_in_edit_mode: true } as Card), {
            initial_effort: '<div class="my-initial-effort"></div>',
        });

        expect(wrapper.find(".taskboard-card-info > .my-initial-effort").exists()).toBe(false);
    });
});
