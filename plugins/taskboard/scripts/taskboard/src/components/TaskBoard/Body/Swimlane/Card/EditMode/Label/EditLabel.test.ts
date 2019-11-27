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

import { shallowMount } from "@vue/test-utils";
import EditLabel from "./EditLabel.vue";

describe("EditLabel", () => {
    it("Displays a texteara with its mirror to edit the label", () => {
        const wrapper = shallowMount(EditLabel, {
            propsData: {
                value: "Lorem ipsum doloret"
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Saves the card if user hits enter", () => {
        const wrapper = shallowMount(EditLabel, {
            propsData: {
                value: "Lorem ipsum doloret"
            }
        });
        wrapper.find({ ref: "textarea" }).trigger("keydown.enter");
        expect(wrapper.emitted("save")).toBeTruthy();
    });

    it("Saves the card if user hits shift + enter", () => {
        const wrapper = shallowMount(EditLabel, {
            propsData: {
                value: "Lorem ipsum doloret"
            }
        });
        wrapper.find({ ref: "textarea" }).trigger("keydown.enter", { shiftKey: true });
        expect(wrapper.emitted("save")).toBeFalsy();
    });
});
