/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { State } from "../../../../store/type";
import { NONE_YET, TRACKER_TEMPLATE } from "../../../../store/type";
import TrackerBaseCard from "./TrackerBaseCard.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-tests";

describe("TrackerBaseCard", () => {
    let mock_set_active_option: jest.Mock;
    beforeEach(() => {
        mock_set_active_option = jest.fn();
    });

    function getWrapper(state: State = {} as State): VueWrapper {
        return shallowMount(TrackerBaseCard, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    mutations: { setActiveOption: mock_set_active_option },
                }),
            },
            props: {
                option_name: TRACKER_TEMPLATE,
            },
        });
    }

    it("Should tell the store it has been selected", () => {
        const state: State = {
            active_option: NONE_YET,
        } as State;

        const wrapper = getWrapper(state);

        wrapper.find("[data-test=selected-option-tracker_template]").setValue(true);

        expect(mock_set_active_option).toHaveBeenCalledWith(expect.anything(), TRACKER_TEMPLATE);
    });
});
