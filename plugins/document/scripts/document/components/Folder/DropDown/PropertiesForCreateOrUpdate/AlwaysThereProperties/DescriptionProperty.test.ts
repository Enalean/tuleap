/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import emitter from "../../../../../helpers/emitter";
import DescriptionProperty from "./DescriptionProperty.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

jest.mock("../../../../../helpers/emitter");

describe("DescriptionProperty", () => {
    function createWrapper(value: string): VueWrapper<InstanceType<typeof DescriptionProperty>> {
        return shallowMount(DescriptionProperty, {
            props: { value },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`When input is updated an event is sent`, async () => {
        const value = "A description";

        const wrapper = createWrapper(value);

        await nextTick();
        const input = wrapper.get("[data-test=document-property-description]");

        if (!(input.element instanceof HTMLTextAreaElement)) {
            throw new Error("input element is not an html input");
        }
        input.element.value = "My new description";
        input.trigger("input");

        expect(emitter.emit).toHaveBeenCalledWith(
            "update-description-property",
            "My new description",
        );
    });
});
