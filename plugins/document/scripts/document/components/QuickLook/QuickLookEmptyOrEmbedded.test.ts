/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import QuickLookEmptyOrEmbedded from "./QuickLookEmptyOrEmbedded.vue";
import localVue from "../../helpers/local-vue";
import { TYPE_EMBEDDED, TYPE_EMPTY } from "../../constants";
import type { Item } from "../../type";

describe("QuickLookEmptyOrEmbedded", () => {
    it("Component can be rendered for embedded", () => {
        const item = {
            type: TYPE_EMBEDDED,
            user_can_write: false,
        } as Item;

        const wrapper = shallowMount(QuickLookEmptyOrEmbedded, {
            localVue,
            propsData: { item: item },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Component can be rendered for empty", () => {
        const item = {
            type: TYPE_EMPTY,
            user_can_write: true,
        } as Item;

        const wrapper = shallowMount(QuickLookEmptyOrEmbedded, {
            localVue,
            propsData: { item: item },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
