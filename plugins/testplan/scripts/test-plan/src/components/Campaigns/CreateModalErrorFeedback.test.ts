/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import CreateModalErrorFeedback from "./CreateModalErrorFeedback.vue";

describe("CreateModalErrorFeedback", () => {
    it("does nothing when there is no error message", () => {
        const wrapper = shallowMount(CreateModalErrorFeedback);

        expect(wrapper.html()).toBe("");
    });

    it("displays the error message when one is provided", () => {
        const error_message = "My custom error message";
        const wrapper = shallowMount(CreateModalErrorFeedback, {
            propsData: {
                error_message,
            },
        });

        expect(wrapper.html()).toContain(error_message);
    });

    it("displays the error message details when one is provided", () => {
        const error_message = "My custom error message";
        const error_message_details = "Full details";
        const wrapper = shallowMount(CreateModalErrorFeedback, {
            propsData: {
                error_message,
                error_message_details,
            },
        });

        expect(wrapper.html()).toContain(error_message);
        expect(wrapper.html()).toContain(error_message_details);
    });
});
