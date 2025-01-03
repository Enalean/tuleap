/**
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

import { REPOSITORIES_SORTED_BY_LAST_UPDATE, REPOSITORIES_SORTED_BY_PATH } from "../../constants";
import DisplayModeSwitcher from "./DisplayModeSwitcher.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { State } from "../../type";

describe("DisplayModeSwitcher", () => {
    function createWrapper(
        display_mode: string,
    ): VueWrapper<InstanceType<typeof DisplayModeSwitcher>> {
        return shallowMount(DisplayModeSwitcher, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        display_mode: display_mode,
                    } as State,
                    actions: {
                        setDisplayMode: () => jest.fn(),
                    },
                }),
            },
        });
    }

    it("displays folders sorted by path", () => {
        const wrapper = createWrapper(REPOSITORIES_SORTED_BY_PATH);
        jest.useFakeTimers();
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays folders sorted by date", () => {
        const wrapper = createWrapper(REPOSITORIES_SORTED_BY_LAST_UPDATE);
        jest.useFakeTimers();
        expect(wrapper.element).toMatchSnapshot();
    });
});
