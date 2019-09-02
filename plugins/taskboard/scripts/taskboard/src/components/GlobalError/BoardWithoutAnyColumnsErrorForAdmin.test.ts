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

import { createLocalVue, shallowMount } from "@vue/test-utils";
import GetTextPlugin from "vue-gettext";
import BoardWithoutAnyColumnsErrorForAdmin from "./BoardWithoutAnyColumnsErrorForAdmin.vue";
import { Vue } from "vue/types/vue";

describe("BoardWithoutAnyColumnsError", () => {
    let local_vue: typeof Vue;
    beforeEach(() => {
        local_vue = createLocalVue();
        local_vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });
    });
    it("is displays misconfiguration error for admin user", () => {
        const wrapper = shallowMount(BoardWithoutAnyColumnsErrorForAdmin, {
            localVue: local_vue,
            propsData: { admin_url: "/path/to/admin" }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
