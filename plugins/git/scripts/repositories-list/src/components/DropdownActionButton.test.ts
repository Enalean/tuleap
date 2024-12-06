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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DropdownActionButton from "./DropdownActionButton.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";

jest.mock("@tuleap/tlp-dropdown"); // ResizeObserver is not defined

function getWrapper(
    is_empty_state: boolean,
): VueWrapper<InstanceType<typeof DropdownActionButton>> {
    return shallowMount(DropdownActionButton, {
        global: { ...getGlobalTestOptions({}) },
        props: { is_empty_state },
    });
}

describe("DropdownActionButton", () => {
    it("displays a dropdown for empty state", () => {
        const wrapper = getWrapper(true);

        expect(wrapper).toMatchSnapshot();
    });

    it("displays a dropdown for repository list", () => {
        const wrapper = getWrapper(false);

        expect(wrapper).toMatchSnapshot();
    });
});
