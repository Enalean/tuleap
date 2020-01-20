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

import { shallowMount } from "@vue/test-utils";
import PeoplePicker from "./PeoplePicker.vue";
import { createTaskboardLocalVue } from "../../../../../../../helpers/local-vue-for-test";
import { UserForPeoplePicker } from "../../../../../../../store/swimlane/card/type";

const select2 = jest.fn();
jest.mock("jquery", () => {
    return (): object => ({
        select2
    });
});

describe("PeoplePicker", () => {
    it("Display a select2 element", async () => {
        const wrapper = shallowMount(PeoplePicker, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                is_multiple: true,
                data: [
                    {
                        id: 101,
                        avatar_url: "steeve.png",
                        label: "Steeve"
                    } as UserForPeoplePicker
                ]
            }
        });

        expect(select2).toBeCalledWith("open");

        expect(wrapper.element).toMatchSnapshot();
    });
});
