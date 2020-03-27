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
import localVue from "../../../helpers/local-vue.js";
import UpdatePermissions from "./UpdatePermissions.vue";
import EventBus from "../../../helpers/event-bus.js";

describe("UpdatePermissions", () => {
    let document_action_button_factory;
    beforeEach(() => {
        document_action_button_factory = (props = {}) => {
            return shallowMount(UpdatePermissions, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`Given a user can not manage the item then the corresponding option is not shown`, () => {
        const item = {
            can_user_manage: false,
        };
        const wrapper = document_action_button_factory({ item });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given a user click on the element then the corresponding modal is opened`, () => {
        const item = {
            can_user_manage: true,
        };
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = document_action_button_factory({ item });

        expect(wrapper.html()).toBeTruthy();

        wrapper.trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith("show-update-permissions-modal", {
            detail: { current_item: item },
        });
    });
});
