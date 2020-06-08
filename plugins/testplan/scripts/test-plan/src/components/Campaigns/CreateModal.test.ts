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

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import * as tlp from "tlp";
import CreateModal from "./CreateModal.vue";
import { Modal } from "tlp";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        modal: jest.fn(),
    };
});

describe("CreateModal", () => {
    let local_vue: typeof Vue;

    beforeEach(async () => {
        local_vue = await createTestPlanLocalVue();
    });

    it("Display the modal when mounted", () => {
        const modal_show = jest.fn();
        jest.spyOn(tlp, "modal").mockImplementation(() => {
            return ({
                show: modal_show,
            } as unknown) as Modal;
        });

        const wrapper = shallowMount(CreateModal, {
            localVue: local_vue,
        });

        expect(modal_show).toHaveBeenCalledTimes(1);
        expect(wrapper.element).toMatchSnapshot();
    });
});
