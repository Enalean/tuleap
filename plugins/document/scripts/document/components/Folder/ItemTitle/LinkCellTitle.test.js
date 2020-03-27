/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import Vuex from "vuex";
import { shallowMount } from "@vue/test-utils";
import LinkCellTitle from "./LinkCellTitle.vue";
import localVue from "../../../helpers/local-vue";
import { TYPE_LINK } from "../../../constants.js";

describe("LinkCellTitle", () => {
    it(`should render link title`, () => {
        const item = {
            id: 42,
            title: "my link",
            link_properties: null,
            type: TYPE_LINK,
        };

        const component_options = {
            localVue,
            propsData: {
                item,
            },
        };

        const store = new Vuex.Store();
        const wrapper = shallowMount(LinkCellTitle, { store, ...component_options });

        expect(wrapper.element).toMatchSnapshot();
    });
});
