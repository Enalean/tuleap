/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import CriterionNumber from "./CriterionNumber.vue";

describe("CriterionNumber", () => {
    it("should render the component", async () => {
        const wrapper = shallowMount(CriterionNumber, {
            propsData: {
                criterion: {
                    name: "id",
                    label: "Id",
                },
                value: "123",
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing text", () => {
        const wrapper = shallowMount(CriterionNumber, {
            propsData: {
                criterion: {
                    name: "id",
                    label: "Id",
                },
                value: "123",
            },
        });

        wrapper.find("[data-test=document-criterion-number-id]").setValue("256");
        expect(wrapper.emitted().input).toStrictEqual([["256"]]);
    });
});
