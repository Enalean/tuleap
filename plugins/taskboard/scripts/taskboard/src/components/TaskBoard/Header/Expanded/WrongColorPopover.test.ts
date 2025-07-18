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
import * as tlp_popovers from "@tuleap/tlp-popovers";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";
import WrongColorPopover from "./WrongColorPopover.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { RootState } from "../../../../store/type";

describe("WrongColorPopover", () => {
    it("initiates a popover to inform user that the chosen color is wrong", () => {
        const tlpCreatePopover = jest.spyOn(tlp_popovers, "createPopover").mockImplementation();

        const wrapper = shallowMount(WrongColorPopover, {
            global: {
                ...getGlobalTestOptions({
                    state: { admin_url: "/path/to/admin" } as RootState,
                }),
            },
            directives: {
                "dompurify-html": buildVueDompurifyHTMLDirective(),
            },
            props: { color: "#87DBEF" },
        });

        expect(wrapper.element).toMatchSnapshot();
        expect(tlpCreatePopover).toHaveBeenCalled();
    });
});
