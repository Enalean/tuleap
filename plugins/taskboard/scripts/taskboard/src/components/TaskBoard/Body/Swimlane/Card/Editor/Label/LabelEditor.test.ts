/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import LabelEditor from "./LabelEditor.vue";
import { getGlobalTestOptions } from "../../../../../../../helpers/global-options-for-test";

function getWrapper(): VueWrapper<InstanceType<typeof LabelEditor>> {
    return shallowMount(LabelEditor, {
        global: { ...getGlobalTestOptions({}) },
        props: {
            value: "Lorem ipsum doloret",
            readonly: false,
        },
    });
}

describe("LabelEditor", () => {
    it("Displays a texteara with its mirror to edit the label", () => {
        const wrapper = getWrapper();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Saves the card if user hits enter", () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=label-editor]").trigger("keydown.enter");
        expect(wrapper.emitted("save")).toBeTruthy();
    });

    it("Does not save the card if user hits shift + enter", () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=label-editor]").trigger("keydown.enter", { shiftKey: true });
        expect(wrapper.emitted("save")).toBeFalsy();
    });
});
