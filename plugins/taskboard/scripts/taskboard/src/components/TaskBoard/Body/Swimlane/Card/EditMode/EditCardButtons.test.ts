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

import { Card, RemainingEffort, TaskboardEvent } from "../../../../../../type";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createTaskboardLocalVue } from "../../../../../../helpers/local-vue-for-test";
import EditCardButtons from "./EditCardButtons.vue";
import CancelSaveButtons from "./CancelSaveButtons.vue";
import EventBus from "../../../../../../helpers/event-bus";

async function createWrapper(
    is_in_edit_mode: boolean,
    remaining_effort: RemainingEffort | null
): Promise<Wrapper<EditCardButtons>> {
    return shallowMount(EditCardButtons, {
        localVue: await createTaskboardLocalVue(),
        propsData: {
            card: {
                is_in_edit_mode,
                remaining_effort
            } as Card
        }
    });
}

describe("EditCardButtons", () => {
    describe("Displays buttons", () => {
        it("displays nothing if remaining effort is not in edit mode", async () => {
            const wrapper = await createWrapper(false, {
                is_in_edit_mode: false
            } as RemainingEffort);

            expect(wrapper.isEmpty()).toBe(true);
        });

        it("displays nothing if there isn't any remaining effort", async () => {
            const wrapper = await createWrapper(false, null);

            expect(wrapper.isEmpty()).toBe(true);
        });

        it("displays buttons if remaining effort is in edit mode", async () => {
            const wrapper = await createWrapper(false, {
                is_in_edit_mode: true
            } as RemainingEffort);

            expect(wrapper.contains(CancelSaveButtons)).toBe(true);
        });

        it("displays buttons if card is in edit mode", async () => {
            const wrapper = await createWrapper(true, null);

            expect(wrapper.contains(CancelSaveButtons)).toBe(true);
        });
    });

    describe("Broadcast events", () => {
        it("Broadcasts cancel-card-edition event when buttons emit a cancel-card-edition event", async () => {
            const wrapper = await createWrapper(true, null);
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            wrapper.find(CancelSaveButtons).vm.$emit("cancel");

            expect(event_bus_emit).toHaveBeenCalledWith(TaskboardEvent.CANCEL_CARD_EDITION, {
                is_in_edit_mode: true,
                remaining_effort: null
            });
        });

        it("Broadcasts save-card-edition event when buttons emit a save-card-edition event", async () => {
            const wrapper = await createWrapper(true, null);
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            wrapper.find(CancelSaveButtons).vm.$emit("save");

            expect(event_bus_emit).toHaveBeenCalledWith(TaskboardEvent.SAVE_CARD_EDITION, {
                is_in_edit_mode: true,
                remaining_effort: null
            });
        });
    });
});
