/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import LinkCellTitle from "./LinkCellTitle.vue";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import { TYPE_LINK } from "../../../constants";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import Vuex from "vuex";
import type { Link, RootState } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";

const localVue = createLocalVue();
localVue.use(Vuex);

describe("LinkCellTitle", () => {
    it(`should render link title`, () => {
        const item = {
            id: 42,
            title: "my link",
            link_properties: {},
            type: TYPE_LINK,
        } as Link;

        const component_options = {
            localVue,
            propsData: {
                item,
            },
        };

        const configuration = { project_id: 101 } as unknown as ConfigurationState;
        const state = { configuration: configuration } as RootState;

        const store_options = {
            state,
        };

        const store = createStoreMock(store_options);
        const wrapper = shallowMount(LinkCellTitle, {
            mocks: { $store: store },
            ...component_options,
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
