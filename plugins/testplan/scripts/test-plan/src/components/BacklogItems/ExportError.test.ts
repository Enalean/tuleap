/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";
import ExportError from "./ExportError.vue";
import * as tlp from "tlp";
import { Modal } from "tlp";

describe("ExportError", () => {
    it("shows the modal on mount", async () => {
        const modal_show_spy = jest.fn();
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return ({
                show: modal_show_spy,
            } as unknown) as Modal;
        });

        shallowMount(ExportError, {
            localVue: await createTestPlanLocalVue(),
        });

        expect(modal_show_spy).toHaveBeenCalledTimes(1);
    });
});
