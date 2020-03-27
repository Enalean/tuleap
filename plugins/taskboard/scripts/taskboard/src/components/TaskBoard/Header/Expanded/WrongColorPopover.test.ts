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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import WrongColorPopover from "./WrongColorPopover.vue";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import * as tlp from "tlp";

jest.mock("tlp");

describe("WrongColorPopover", () => {
    it("initiates a popover to inform user that the chosen color is wrong", async () => {
        const tlpCreatePopover = jest.spyOn(tlp, "createPopover").mockImplementation();

        const wrapper = shallowMount(WrongColorPopover, {
            localVue: await createTaskboardLocalVue(),
            mocks: { $store: createStoreMock({ state: { admin_url: "/path/to/admin" } }) },
            propsData: { color: "#87DBEF" },
        });

        expect(wrapper.element).toMatchSnapshot();
        expect(tlpCreatePopover).toHaveBeenCalled();
    });
});
