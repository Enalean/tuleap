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

import { Card, TaskboardEvent } from "../../../../../../type";
import { shallowMount, Wrapper } from "@vue/test-utils";
import EditCardButtons from "./EditCardButtons.vue";
import CancelSaveButtons from "./CancelSaveButtons.vue";
import EventBus from "../../../../../../helpers/event-bus";

function createWrapper(card: Card): Wrapper<EditCardButtons> {
    return shallowMount(EditCardButtons, {
        propsData: { card },
    });
}

describe("EditCardButtons", () => {
    describe("Displays buttons", () => {
        it("displays nothing if remaining effort is not in edit mode", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: false,
                remaining_effort: {
                    is_in_edit_mode: false,
                },
            } as Card);

            expect(wrapper.isEmpty()).toBe(true);
        });

        it("displays nothing if there isn't any remaining effort", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: false,
                remaining_effort: null,
            } as Card);

            expect(wrapper.isEmpty()).toBe(true);
        });

        it("displays buttons if remaining effort is in edit mode", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: false,
                remaining_effort: { is_in_edit_mode: true },
            } as Card);

            expect(wrapper.contains(CancelSaveButtons)).toBe(true);
        });

        it("displays buttons if card is in edit mode", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: true,
                remaining_effort: null,
            } as Card);

            expect(wrapper.contains(CancelSaveButtons)).toBe(true);
        });
    });

    describe("Broadcast events", () => {
        it("Broadcasts cancel-card-edition event when buttons emit a cancel event", () => {
            const card = { is_in_edit_mode: true, remaining_effort: null } as Card;
            const wrapper = createWrapper(card);
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            wrapper.get(CancelSaveButtons).vm.$emit("cancel");

            expect(event_bus_emit).toHaveBeenCalledWith(TaskboardEvent.CANCEL_CARD_EDITION, card);
        });

        it("Broadcasts save-card-edition event when buttons emit a save event", () => {
            const card = {
                is_in_edit_mode: true,
                remaining_effort: null,
            } as Card;
            const wrapper = createWrapper(card);
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            wrapper.get(CancelSaveButtons).vm.$emit("save");

            expect(event_bus_emit).toHaveBeenCalledWith(TaskboardEvent.SAVE_CARD_EDITION, card);
        });
    });

    it(`when the card is being saved,
        it will set the action ongoing flag to true`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: true,
            is_being_saved: true,
        } as Card);
        const buttons = wrapper.get(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(true);
    });

    it(`when the card is not being saved and has no remaining effort,
        it will set the action ongoing flag to false`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: true,
            remaining_effort: null,
            is_being_saved: false,
        } as Card);
        const buttons = wrapper.get(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(false);
    });

    it(`when the remaining effort is being saved,
        it will set the action ongoing flag to true`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: false,
            remaining_effort: { is_in_edit_mode: true, is_being_saved: true },
            is_being_saved: false,
        } as Card);
        const buttons = wrapper.get(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(true);
    });
});
