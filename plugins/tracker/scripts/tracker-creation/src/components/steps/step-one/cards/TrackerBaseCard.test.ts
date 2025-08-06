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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { State } from "../../../../store/type";
import { NONE_YET, TRACKER_TEMPLATE } from "../../../../store/type";
import TrackerBaseCard from "./TrackerBaseCard.vue";
import type Vue from "vue";

describe("TrackerBaseCard", () => {
    function getWrapper(state: State = {} as State): Wrapper<Vue> {
        return shallowMount(TrackerBaseCard, {
            propsData: {
                optionName: TRACKER_TEMPLATE,
            },
            mocks: {
                $store: createStoreMock({
                    state,
                }),
            },
        });
    }

    it("Should tell the store it has been selected", () => {
        const state: State = {
            active_option: NONE_YET,
        } as State;

        const wrapper = getWrapper(state);

        wrapper.find("[data-test=selected-option-tracker_template]").setChecked(true);

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setActiveOption", TRACKER_TEMPLATE);
    });
});
