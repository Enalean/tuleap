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

import { shallowMount } from "@vue/test-utils";
import App from "./App.vue";

describe("App", () => {
    it("is displays misconfiguration error for regular user", () => {
        const wrapper = shallowMount(App, {
            propsData: { user_is_admin: false, admin_url: "/path/to/admin" }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("is displays misconfiguration error for admin user", () => {
        const wrapper = shallowMount(App, {
            propsData: { user_is_admin: true, admin_url: "/path/to/admin" }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
