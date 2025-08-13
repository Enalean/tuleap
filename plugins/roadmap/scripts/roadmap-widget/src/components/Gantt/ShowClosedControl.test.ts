/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { RootState } from "../../store/type";
import ShowClosedControl from "./ShowClosedControl.vue";

describe("ShowClosedControl", () => {
    let retrieveSpy: jest.Mock, show_closed_elements: boolean;

    beforeEach(() => {
        retrieveSpy = jest.fn();
    });

    function getWrapper(): VueWrapper {
        return shallowMount(ShowClosedControl, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        show_closed_elements: show_closed_elements,
                    } as RootState,
                    mutations: {
                        toggleClosedElements: () => retrieveSpy(show_closed_elements),
                    },
                }),
            },
        });
    }

    it("should mutate the store when the user decides to show/hide the closed elements", () => {
        show_closed_elements = false;
        getWrapper().find("[data-test=input]").setValue();
        expect(retrieveSpy).toHaveBeenCalledWith(show_closed_elements);

        show_closed_elements = true;
        getWrapper().find("[data-test=input]").setValue();
        expect(retrieveSpy).toHaveBeenCalledWith(show_closed_elements);
    });
});
