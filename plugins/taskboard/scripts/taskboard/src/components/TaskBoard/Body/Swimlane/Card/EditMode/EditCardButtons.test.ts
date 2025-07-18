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

import type { Card } from "../../../../../../type";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import EditCardButtons from "./EditCardButtons.vue";
import CancelSaveButtons from "./CancelSaveButtons.vue";
import emitter from "../../../../../../helpers/emitter";

function createWrapper(card: Card): VueWrapper<InstanceType<typeof EditCardButtons>> {
    return shallowMount(EditCardButtons, {
        props: { card },
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

            expect(wrapper.html()).toBe("<!--v-if-->");
        });

        it("displays nothing if there isn't any remaining effort", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: false,
                remaining_effort: null,
            } as Card);

            expect(wrapper.html()).toBe("<!--v-if-->");
        });

        it("displays buttons if remaining effort is in edit mode", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: false,
                remaining_effort: { is_in_edit_mode: true },
            } as Card);

            expect(wrapper.findComponent(CancelSaveButtons).exists()).toBe(true);
        });

        it("displays buttons if card is in edit mode", () => {
            const wrapper = createWrapper({
                is_in_edit_mode: true,
                remaining_effort: null,
            } as Card);

            expect(wrapper.findComponent(CancelSaveButtons).exists()).toBe(true);
        });
    });

    describe("Broadcast events", () => {
        it("Broadcasts cancel-card-edition and editor-closed events when buttons emit a cancel event", () => {
            const card = { is_in_edit_mode: true, remaining_effort: null } as Card;
            const wrapper = createWrapper(card);

            let received_cancel = false;
            emitter.on("cancel-card-edition", () => {
                received_cancel = true;
            });

            wrapper.findComponent(CancelSaveButtons).vm.$emit("cancel");

            expect(received_cancel).toBe(true);
            expect(wrapper.emitted("editor-closed")).toBeTruthy();
        });

        it("Broadcasts save-card-edition and editor-closed events when buttons emit a save event", () => {
            const card = {
                is_in_edit_mode: true,
                remaining_effort: null,
            } as Card;
            const wrapper = createWrapper(card);

            let received_save = false;
            emitter.on("save-card-edition", () => {
                received_save = true;
            });

            wrapper.findComponent(CancelSaveButtons).vm.$emit("save");

            expect(received_save).toBe(true);
            expect(wrapper.emitted("editor-closed")).toBeTruthy();
        });
    });

    it(`when the card is being saved,
        it will set the action ongoing flag to true`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: true,
            is_being_saved: true,
        } as Card);
        const buttons = wrapper.findComponent(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(true);
    });

    it(`when the card is not being saved and has no remaining effort,
        it will set the action ongoing flag to false`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: true,
            remaining_effort: null,
            is_being_saved: false,
        } as Card);
        const buttons = wrapper.findComponent(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(false);
    });

    it(`when the remaining effort is being saved,
        it will set the action ongoing flag to true`, () => {
        const wrapper = createWrapper({
            is_in_edit_mode: false,
            remaining_effort: { is_in_edit_mode: true, is_being_saved: true },
            is_being_saved: false,
        } as Card);
        const buttons = wrapper.findComponent(CancelSaveButtons);
        expect(buttons.props("is_action_ongoing")).toBe(true);
    });
});
