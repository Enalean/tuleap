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
import { createStoreMock } from "../../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTaskboardLocalVue } from "../../../../../../helpers/local-vue-for-test";
import EditRemainingEffort from "./EditRemainingEffort.vue";
import { RootState } from "../../../../../../store/type";
import { Card, TaskboardEvent } from "../../../../../../type";
import EventBus from "../../../../../../helpers/event-bus";

async function getWrapper(): Promise<Wrapper<EditRemainingEffort>> {
    return shallowMount(EditRemainingEffort, {
        localVue: await createTaskboardLocalVue(),
        propsData: {
            card: {
                id: 42,
                color: "fiesta-red",
                remaining_effort: {
                    value: 3.14,
                    is_in_edit_mode: true
                }
            } as Card
        },
        mocks: {
            $store: createStoreMock({
                state: {
                    swimlane: {}
                } as RootState
            })
        }
    });
}

describe("EditRemainingEffort", () => {
    it("Displays a text input", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.attributes("type")).toBe("text");
        expect(wrapper.attributes("aria-label")).toBe("New remaining effort");
    });

    it("Does not save anything if user hit enter but didn't change the initial value", async () => {
        const wrapper = await getWrapper();

        wrapper.trigger("keyup.enter");
        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
        expect(wrapper.props("card").remaining_effort.is_in_edit_mode).toBe(false);
    });

    it("Saves the new value if user hits enter and it stays in edit mode to wait the REST call to be done", async () => {
        const wrapper = await getWrapper();

        const value = 42;
        wrapper.setData({ value });
        wrapper.trigger("keyup.enter");

        const card = wrapper.props("card");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/saveRemainingEffort", {
            card,
            value
        });
        expect(card.remaining_effort.is_in_edit_mode).toBe(true);
    });

    it(`Saves the new value if user clicks on save button (that is outside of this component)
        and it stays in edit mode to wait the REST call to be done`, async () => {
        const wrapper = await getWrapper();

        const value = 42;
        wrapper.setData({ value });

        const card = wrapper.props("card");
        EventBus.$emit(TaskboardEvent.SAVE_CARD_EDITION, card);

        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("swimlane/saveRemainingEffort", {
            card,
            value
        });
        expect(card.remaining_effort.is_in_edit_mode).toBe(true);
    });

    it(`Cancels the edition of the remaining effort if user clicks on cancel button (that is outside of this componenet)`, async () => {
        const wrapper = await getWrapper();

        const value = 42;
        wrapper.setData({ value });

        const card = wrapper.props("card");
        EventBus.$emit(TaskboardEvent.CANCEL_CARD_EDITION, card);

        expect(card.remaining_effort.is_in_edit_mode).toBe(false);
        expect(card.remaining_effort.value).toBe(3.14);
    });

    it(`does not save anynthing if user clicks on save button for another card`, async () => {
        const wrapper = await getWrapper();

        const value = 42;
        wrapper.setData({ value });

        const card = wrapper.props("card");
        EventBus.$emit(TaskboardEvent.SAVE_CARD_EDITION, {} as Card);

        expect(wrapper.vm.$store.dispatch).not.toHaveBeenCalled();
        expect(card.remaining_effort.is_in_edit_mode).toBe(true);
    });

    it(`does not cancel the edition of the remaining effort if user clicks on cancel button for another card`, async () => {
        const wrapper = await getWrapper();

        const value = 42;
        wrapper.setData({ value });

        const card = wrapper.props("card");
        EventBus.$emit(TaskboardEvent.CANCEL_CARD_EDITION, {} as Card);

        expect(card.remaining_effort.is_in_edit_mode).toBe(true);
    });

    it("Adjust the size of the input whenever user enters digits", async () => {
        const wrapper = await getWrapper();

        wrapper.setData({ value: "3" });
        expect(wrapper.classes()).toEqual(["taskboard-card-remaining-effort-input"]);

        wrapper.setData({ value: "3.14" });
        expect(wrapper.classes()).toContain("taskboard-card-remaining-effort-input-width-40");

        wrapper.setData({ value: "3.14159265358979323846264338327950288" });
        expect(wrapper.classes()).toContain("taskboard-card-remaining-effort-input-width-60");
    });
});
