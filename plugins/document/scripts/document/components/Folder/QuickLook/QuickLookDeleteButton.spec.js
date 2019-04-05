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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";

import QuickLookDeleteButton from "./QuickLookDeleteButton.vue";

describe("QuickLookDeleteButton", () => {
    let delete_button_factory;
    beforeEach(() => {
        delete_button_factory = user_can_write => {
            return shallowMount(QuickLookDeleteButton, {
                localVue,
                propsData: {
                    item: {
                        user_can_write: user_can_write
                    }
                }
            });
        };
    });
    it(`Displays the delete button because the user can write`, () => {
        const wrapper = delete_button_factory(true);
        expect(wrapper.find("[data-test=quick-look-delete-button]").exists()).toBeTruthy();
    });
    it(`Does not display the delete button if the can't write`, () => {
        const wrapper = delete_button_factory(false);
        expect(wrapper.find("[data-test=quick-look-delete-button]").exists()).toBeFalsy();
    });
});
