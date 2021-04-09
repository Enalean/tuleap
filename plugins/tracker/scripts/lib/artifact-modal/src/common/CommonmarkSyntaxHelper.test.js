/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import * as popovers from "tlp";
import CommonmarkSyntaxHelper from "./CommonmarkSyntaxHelper.vue";
import { setCatalog } from "../gettext-catalog";

const emptyFunction = () => {
    //Do nothing
};

describe(`CommonmarkSyntaxHelper`, () => {
    beforeEach(() => {
        setCatalog({
            getString: (msgid) => msgid,
        });
    });

    it(`closes the popover on Escape`, () => {
        const fake_popover = {
            hide: emptyFunction,
            destroy: emptyFunction,
        };
        jest.spyOn(popovers, "createPopover").mockReturnValue(fake_popover);
        const hide = jest.spyOn(fake_popover, "hide");
        shallowMount(CommonmarkSyntaxHelper, { disabled: false });

        document.dispatchEvent(
            new KeyboardEvent("keyup", {
                key: "Escape",
            })
        );

        expect(hide).toHaveBeenCalled();
    });

    describe(`destroy()`, () => {
        it(`removes the event listeners and destroys the popover`, () => {
            const removeListener = jest.spyOn(document, "removeEventListener");
            const fake_popover = {
                destroy: emptyFunction,
            };
            const destroyPopover = jest.spyOn(fake_popover, "destroy");
            jest.spyOn(popovers, "createPopover").mockReturnValue(fake_popover);
            const wrapper = shallowMount(CommonmarkSyntaxHelper, { disabled: false });
            wrapper.destroy();

            expect(destroyPopover).toHaveBeenCalled();
            expect(removeListener).toHaveBeenCalled();
        });
    });
});
