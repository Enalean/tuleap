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

import StepOne from "./StepOne.vue";
import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { State } from "../../../store/type";

describe("StepOne", () => {
    it("resets the slugify mode when it is mounted", () => {
        const wrapper: Wrapper<StepOne> = shallowMount(StepOne, {
            mocks: {
                $store: createStoreMock({
                    state: {} as State
                })
            }
        });

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSlugifyShortnameMode", true);
    });
});
