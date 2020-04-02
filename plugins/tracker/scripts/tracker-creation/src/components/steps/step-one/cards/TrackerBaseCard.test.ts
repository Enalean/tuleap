/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { CreationOptions, State } from "../../../../store/type";
import TrackerBaseCard from "./TrackerBaseCard.vue";

describe("TrackerBaseCard", () => {
    function getWrapper(state: State = {} as State): Wrapper<TrackerBaseCard> {
        return shallowMount(TrackerBaseCard, {
            propsData: {
                optionName: CreationOptions.TRACKER_TEMPLATE,
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
            active_option: CreationOptions.NONE_YET,
        } as State;

        const wrapper: Wrapper<TrackerBaseCard> = getWrapper(state);

        wrapper.get("[data-test=selected-option]").trigger("click");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setActiveOption",
            CreationOptions.TRACKER_TEMPLATE
        );
    });
});
