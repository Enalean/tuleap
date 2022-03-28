/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { createLocalVue, shallowMount } from "@vue/test-utils";
import VueDOMPurifyHTML from "vue-dompurify-html";
import GetTextPlugin from "vue-gettext";
import DropdownActionButton from "./DropdownActionButton.vue";

jest.mock("tlp");

describe("DropdownActionButton", () => {
    it("displays a dropdown for empty state", () => {
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        const wrapper = shallowMount(DropdownActionButton, {
            localVue,
            propsData: {
                is_empty_state: true,
            },
        });

        expect(wrapper).toMatchSnapshot();
    });

    it("displays a dropdown for repository list", () => {
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });

        const wrapper = shallowMount(DropdownActionButton, {
            localVue,
            propsData: {
                is_empty_state: false,
            },
        });

        expect(wrapper).toMatchSnapshot();
    });
});
