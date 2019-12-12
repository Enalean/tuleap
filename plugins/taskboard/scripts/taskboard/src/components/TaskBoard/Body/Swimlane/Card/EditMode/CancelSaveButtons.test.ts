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
import { TaskboardEvent } from "../../../../../../type";
import { createTaskboardLocalVue } from "../../../../../../helpers/local-vue-for-test";
import EventBus from "../../../../../../helpers/event-bus";

async function createWrapper(): Promise<Wrapper<CancelSaveButtons>> {
    return shallowMount(CancelSaveButtons, {
        localVue: await createTaskboardLocalVue()
    });
}

describe("CancelSaveButtons", () => {
    it("Emits cancel-card-edition event when user clicks on cancel button", async () => {
        const wrapper = await createWrapper();
        const cancel = wrapper.find("[data-test=cancel]");

        cancel.trigger("click");

        expect(wrapper.emitted("cancel")).toBeTruthy();
    });

    it("Emits a cancel event when user press ESC key", async () => {
        const wrapper = await createWrapper();

        EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);

        expect(wrapper.emitted("cancel")).toBeTruthy();
    });

    it("Emits a save event when user clicks on save button", async () => {
        const wrapper = await createWrapper();
        const cancel = wrapper.find("[data-test=save]");

        cancel.trigger("click");

        expect(wrapper.emitted("save")).toBeTruthy();
    });
});
