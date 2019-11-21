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
import CancelSaveButtons from "./CancelSaveButtons.vue";
import { Card, Event, RemainingEffort } from "../../../../../../type";
import { createTaskboardLocalVue } from "../../../../../../helpers/local-vue-for-test";
import EventBus from "../../../../../../helpers/event-bus";

async function createWrapper(
    is_in_edit_mode: boolean,
    remaining_effort: RemainingEffort | null
): Promise<Wrapper<CancelSaveButtons>> {
    return shallowMount(CancelSaveButtons, {
        localVue: await createTaskboardLocalVue(),
        propsData: {
            card: {
                is_in_edit_mode,
                remaining_effort
            } as Card
        }
    });
}

describe("CancelSaveButtons", () => {
    it("displays nothing if there isn't any remaining effort", async () => {
        const wrapper = await createWrapper(false, null);

        expect(wrapper.isEmpty()).toBe(true);
    });

    it("displays nothing if remaining effort is not in edit mode", async () => {
        const wrapper = await createWrapper(false, { is_in_edit_mode: false } as RemainingEffort);

        expect(wrapper.isEmpty()).toBe(true);
    });

    it("displays buttons if remaining effort is in edit mode", async () => {
        const wrapper = await createWrapper(false, { is_in_edit_mode: true } as RemainingEffort);

        expect(wrapper.contains("[data-test=cancel]")).toBe(true);
        expect(wrapper.contains("[data-test=save]")).toBe(true);
    });

    it("displays buttons if card is in edit mode", async () => {
        const wrapper = await createWrapper(true, null);

        expect(wrapper.contains("[data-test=cancel]")).toBe(true);
        expect(wrapper.contains("[data-test=save]")).toBe(true);
    });

    it("Emits cancel-card-edition event when user clicks on cancel button", async () => {
        const wrapper = await createWrapper(true, null);
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const cancel = wrapper.find("[data-test=cancel]");
        cancel.trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            Event.CANCEL_CARD_EDITION,
            wrapper.props("card")
        );
    });

    it("Emits save-card-edition event when user clicks on save button", async () => {
        const wrapper = await createWrapper(true, null);
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const cancel = wrapper.find("[data-test=save]");
        cancel.trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(Event.SAVE_CARD_EDITION, wrapper.props("card"));
    });
});
