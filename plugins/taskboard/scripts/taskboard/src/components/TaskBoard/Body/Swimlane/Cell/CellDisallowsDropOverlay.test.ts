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
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";

describe("CellDisallowsDropOverlay", () => {
    it("displays div with an icon and an error message", () => {
        const wrapper = shallowMount(CellDisallowsDropOverlay, {
            props: {
                is_column_collapsed: false,
            },
            global: { ...getGlobalTestOptions({}) },
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does not render the error message when the column is collapsed", () => {
        const wrapper = shallowMount(CellDisallowsDropOverlay, {
            props: {
                is_column_collapsed: true,
            },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(wrapper.find("[data-test=overlay-error-message]").exists()).toBe(false);
    });
});
