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

import localVue from "../../../../../helpers/local-vue";
import { shallowMount } from "@vue/test-utils";
import OwnerProperty from "./OwnerProperty.vue";
import type { User } from "../../../../../type";
import PeoplePicker from "../PeoplePicker.vue";

describe("OwnerProperty", () => {
    it(`Given owner value is updated
        Then an event is emitted with the new user id choosen`, async () => {
        const wrapper = shallowMount(OwnerProperty, {
            localVue,
            propsData: {
                value: {
                    id: 137,
                } as User,
            },
        });

        await wrapper.findComponent(PeoplePicker).vm.$emit("input", 102);

        const event = wrapper.emitted().input;
        if (!event) {
            throw Error("Input event not emitted");
        }

        expect(event[0][0]).toStrictEqual({
            id: 102,
        });
    });
});
