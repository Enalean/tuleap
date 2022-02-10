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
import CriterionText from "./CriterionText.vue";

describe("CriterionText", () => {
    it("should render the component", async () => {
        const wrapper = shallowMount(CriterionText, {
            propsData: {
                name: "title",
                value: "Lorem",
                label: "Title",
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing text", () => {
        const wrapper = shallowMount(CriterionText, {
            propsData: {
                name: "title",
                value: "Lorem",
                label: "Title",
            },
        });

        wrapper.find("[data-test=document-criterion-text-title]").setValue("Lorem ipsum");
        expect(wrapper.emitted().input).toStrictEqual([["Lorem ipsum"]]);
    });
});
