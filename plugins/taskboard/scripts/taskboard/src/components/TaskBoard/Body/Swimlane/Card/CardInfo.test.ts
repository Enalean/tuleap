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
import CardInfo from "./CardInfo.vue";
import type { Card, Tracker, User } from "../../../../../type";
import CardAssignees from "./CardAssignees.vue";
import UserAvatar from "./UserAvatar.vue";

function getCard(is_in_edit_mode: boolean): Card {
    const assignees = [{ id: 196 } as User];
    return {
        id: 43,
        color: "lake-placid-blue",
        assignees,
        is_in_edit_mode,
    } as Card;
}

describe("CardInfo", () => {
    let user_can_edit_assignees: boolean;
    beforeEach(() => {
        user_can_edit_assignees = true;
    });

    function getWrapper(card: Card, slots = {}): VueWrapper<InstanceType<typeof CardInfo>> {
        const tracker: Tracker = user_can_edit_assignees
            ? ({ assigned_to_field: { id: 619, is_multiple: false } } as Tracker)
            : ({ assigned_to_field: null } as Tracker);

        return shallowMount(CardInfo, {
            props: { card, tracker },
            slots,
        });
    }

    it("Displays the assignees avatars when the card is in read mode", () => {
        const wrapper = getWrapper(getCard(false));

        expect(wrapper.findComponent(UserAvatar).exists()).toBe(true);
    });

    it(`Displays the assignees avatars when the user cannot edit assignees`, () => {
        user_can_edit_assignees = false;
        const wrapper = getWrapper(getCard(false));

        expect(wrapper.findComponent(UserAvatar).exists()).toBe(true);
    });

    it("Includes the initial effort slot", () => {
        const wrapper = getWrapper(getCard(false), {
            initial_effort: '<div class="my-initial-effort"></div>',
        });

        expect(wrapper.find(".taskboard-card-info > .my-initial-effort").exists()).toBe(true);
    });

    it("Does not include the initial effort slot if card is in edit mode", () => {
        const wrapper = getWrapper(getCard(true), {
            initial_effort: '<div class="my-initial-effort"></div>',
        });

        expect(wrapper.find(".taskboard-card-info > .my-initial-effort").exists()).toBe(false);
    });

    it(`Displays the assignees editor when the card is in edit mode`, () => {
        const wrapper = getWrapper(getCard(true));

        expect(wrapper.findComponent(CardAssignees).exists()).toBe(true);
    });
});
