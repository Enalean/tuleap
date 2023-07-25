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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ParentCardRemainingEffort from "./ParentCardRemainingEffort.vue";
import type { Card, RemainingEffort } from "../../../../../type";
import { createTaskboardLocalVue } from "../../../../../helpers/local-vue-for-test";
import EditRemainingEffort from "./RemainingEffort/EditRemainingEffort.vue";

async function getWrapper(
    remaining_effort: RemainingEffort | null
): Promise<Wrapper<ParentCardRemainingEffort>> {
    return shallowMount(ParentCardRemainingEffort, {
        localVue: await createTaskboardLocalVue(),
        propsData: {
            card: {
                remaining_effort,
                color: "lake-placid-blue",
            } as Card,
        },
    });
}

describe("ParentCardRemainingEffort", () => {
    it("displays the remaining effort of the parent card in a badge", async () => {
        const wrapper = await getWrapper({ value: 666 } as RemainingEffort);

        expect(wrapper.classes("tlp-badge-lake-placid-blue")).toBe(true);
        expect(wrapper.classes("tlp-swatch-lake-placid-blue")).toBe(true);
        expect(wrapper.text()).toBe("666");
        expect(wrapper.find("i[class~=fa-flag-checkered]").exists()).toBe(true);
        expect(wrapper.attributes("title")).toBe("Remaining effort");
    });

    it("displays a spinner instead of a flag if remaining effort is being saved", async () => {
        const wrapper = await getWrapper({ value: 666, is_being_saved: true } as RemainingEffort);

        expect(wrapper.find("i[class~=fa-flag-checkered]").exists()).toBe(false);
        expect(wrapper.find("i[class~=fa-circle-o-notch][class~=fa-spin]").exists()).toBe(true);
    });

    it("displays an input instead of the value if the remaining effort is in edit mode", async () => {
        const wrapper = await getWrapper({ value: 666, is_in_edit_mode: true } as RemainingEffort);

        expect(wrapper.text()).toBe("");
        expect(wrapper.findComponent(EditRemainingEffort).exists()).toBe(true);
    });

    it("is a focusable button if remaining effort can be updated", async () => {
        const wrapper = await getWrapper({
            value: 666,
            can_update: true,
            is_in_edit_mode: false,
        } as RemainingEffort);

        expect(wrapper.attributes("tabindex")).toBe("0");
        expect(wrapper.attributes("role")).toBe("button");
        wrapper.trigger("click");
        expect(wrapper.props("card").remaining_effort.is_in_edit_mode).toBe(true);
    });

    it("is not a focusable button if remaining effort can not be updated", async () => {
        const wrapper = await getWrapper({
            value: 666,
            can_update: false,
            is_in_edit_mode: false,
        } as RemainingEffort);

        expect(wrapper.attributes("tabindex")).toBe("-1");
        expect(wrapper.attributes("role")).toBe("");
        wrapper.trigger("click");
        expect(wrapper.props("card").remaining_effort.is_in_edit_mode).toBe(false);
    });

    it("displays nothing if the parent card has no remaining effort field", async () => {
        const wrapper = await getWrapper(null);

        expect(wrapper.html()).toBe("");
    });

    it("displays nothing if the parent card has no remaining effort value", async () => {
        const wrapper = await getWrapper({ value: null } as RemainingEffort);

        expect(wrapper.html()).toBe("");
    });

    it("sends a `editor-closed` event when the edition of remaining effort is closed", async () => {
        const wrapper = await getWrapper({ value: 666, is_in_edit_mode: true } as RemainingEffort);

        const edit_remaining_effort = wrapper.findComponent(EditRemainingEffort);
        edit_remaining_effort.vm.$emit("editor-closed");

        expect(wrapper.emitted("editor-closed")).toBeTruthy();
    });
});
