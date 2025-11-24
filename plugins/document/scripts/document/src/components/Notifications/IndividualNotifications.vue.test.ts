/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it, beforeEach } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import IndividualNotifications from "./IndividualNotifications.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("IndividualNotifications", () => {
    let is_a_folder: boolean, is_user_anonymous: boolean;

    beforeEach(() => {
        is_a_folder = true;
        is_user_anonymous = false;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(IndividualNotifications, {
            props: {
                is_user_notified: true,
                is_user_notified_for_cascade: true,
                is_a_folder,
                is_user_anonymous,
                item_id: 12,
                action_url: "something",
                csrf_token: { name: "csrf", value: "csrf" },
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });
    }

    it("should display the sub-hierarchy checkbox if item is a folder", () => {
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=notify-me-hierarchy-checkbox-input]").exists()).toBe(true);
    });

    it("should not display the sub-hierarchy checkbox if item is not a folder", () => {
        is_a_folder = false;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=notify-me-hierarchy-checkbox-input]").exists()).toBe(false);
    });

    it("should display the checkbox as disabled if user is anonymous", () => {
        is_user_anonymous = true;
        const wrapper = getWrapper();
        const input_checkbox = wrapper.find<HTMLInputElement>(
            "[data-test=notify-me-checkbox-input]",
        );

        expect(input_checkbox.element.disabled).toBe(true);
    });
});
