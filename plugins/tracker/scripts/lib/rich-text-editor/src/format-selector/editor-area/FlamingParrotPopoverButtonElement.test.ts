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

import "./FlamingParrotPopoverButtonElement";

interface FakeBootstrap {
    popover(): void;
}

const popoverSpy = jest.fn();

jest.mock("jquery", () => {
    return {
        default: (): FakeBootstrap => {
            return { popover: popoverSpy };
        },
    };
});

describe(`FlamingParrotPopoverButtonElement`, () => {
    let custom_element: Element;
    beforeEach(() => {
        popoverSpy.mockReset();

        custom_element = document.createElement("fp-popover-button");
        custom_element.innerHTML = `
          <button data-button></button>
          <template data-popover-content>
            <section data-popover-root>Popover content</section>
          </template>`;
    });

    afterEach(() => {
        custom_element.remove();
    });

    it(`connectedCallback() creates a popover from [data-button] and [data-popover-content]`, () => {
        document.body.append(custom_element);
        expect(popoverSpy).toHaveBeenCalled();
    });

    it(`disconnectedCallback() destroys the popover and removes the Escape listener`, () => {
        const removeListener = jest.spyOn(document, "removeEventListener");
        document.body.append(custom_element);
        custom_element.remove();

        expect(popoverSpy).toHaveBeenCalledWith("destroy");
        expect(removeListener).toHaveBeenCalled();
    });

    it(`when I hit the "Escape" button, the popover will be hidden`, () => {
        document.body.append(custom_element);
        document.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape" }));
        expect(popoverSpy).toHaveBeenCalledWith("hide");
    });
});
