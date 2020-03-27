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

import { Vue } from "vue/types/vue";
import { shallowMount } from "@vue/test-utils";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import BoardWithoutAnyColumnsErrorForAdmin from "./BoardWithoutAnyColumnsErrorForAdmin.vue";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";

describe("BoardWithoutAnyColumnsError", () => {
    let local_vue: typeof Vue;

    beforeEach(async () => {
        local_vue = await createTaskboardLocalVue();
    });

    it("is displays misconfiguration error for admin user", () => {
        const wrapper = shallowMount(BoardWithoutAnyColumnsErrorForAdmin, {
            localVue: local_vue,
            mocks: { $store: createStoreMock({ state: { admin_url: "/path/to/admin" } }) },
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});
