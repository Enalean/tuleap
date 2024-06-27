/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TroveCategoryList from "./TroveCategoryList.vue";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("TroveCategoryList -", () => {
    let factory: VueWrapper;
    beforeEach(() => {
        const trove_categories = {
            id: "1",
            shortname: "licence",
            fullname: "licence",
            children: [
                {
                    id: "10",
                    shortname: "MIT",
                    fullname: "MIT Licence",
                    children: [],
                },
                {
                    id: "20",
                    shortname: "GPL",
                    fullname: "GNU General Public License ",
                    children: [],
                },
            ],
            is_description_required: false,
        };

        factory = shallowMount(TroveCategoryList, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: { trovecat: trove_categories },
        });
    });

    it("Display correctly the component", () => {
        const wrapper = factory;

        expect(wrapper).toMatchSnapshot();
    });

    it("Send an event when user chooses a category", () => {
        const emit = jest.spyOn(emitter, "emit");

        const wrapper = factory;
        const second_option = wrapper.findAll("option").at(2);
        if (second_option === undefined) {
            throw new Error("error");
        }
        (second_option.element as HTMLOptionElement).selected = true;

        wrapper.get("[data-test=trove-category-list]").trigger("change");

        expect(emit).toHaveBeenCalledWith("choose-trove-cat", {
            category_id: "1",
            value_id: "20",
        });
    });
});
