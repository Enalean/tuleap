/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import "./BurningParrotPopoverButtonElement";
import * as tlp_popovers from "@tuleap/tlp-popovers";

jest.mock("@tuleap/tlp-popovers");

const noop = (): void => {
    // Do nothing
};

describe(`BurningParrotPopoverButtonElement`, () => {
    let custom_element: Element;
    beforeEach(() => {
        custom_element = document.createElement("bp-popover-button");
        custom_element.innerHTML = `
          <button data-button></button>
          <section data-popover-content>Popover content</section>
        `;
    });

    afterEach(() => {
        custom_element.remove();
    });

    it(`connectedCallback() creates a popover from [data-button] and [data-popover-content]`, () => {
        const mocked_popover_instance = {
            show: noop,
            hide: noop,
            destroy: noop,
        };
        const createPopover = jest.spyOn(tlp_popovers, "createPopover");
        createPopover.mockReturnValue(mocked_popover_instance);

        document.body.append(custom_element);

        expect(createPopover).toHaveBeenCalledTimes(1);
    });

    it(`disconnectedCallback() destroys the popover and removes the Escape listener`, () => {
        const mocked_popover_instance = {
            show: noop,
            hide: noop,
            destroy: jest.fn(),
        };
        const createPopover = jest.spyOn(tlp_popovers, "createPopover");
        createPopover.mockReturnValue(mocked_popover_instance);

        const removeListener = jest.spyOn(document, "removeEventListener");
        document.body.append(custom_element);
        custom_element.remove();

        expect(removeListener).toHaveBeenCalled();
        expect(mocked_popover_instance.destroy).toHaveBeenCalled();
    });

    it(`when I hit the "Escape" button, the popover will be hidden`, () => {
        const mocked_popover_instance = {
            show: noop,
            hide: jest.fn(),
            destroy: noop,
        };
        const createPopover = jest.spyOn(tlp_popovers, "createPopover");
        createPopover.mockReturnValue(mocked_popover_instance);

        document.body.append(custom_element);

        document.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape" }));
        expect(mocked_popover_instance.hide).toHaveBeenCalled();
    });
});
