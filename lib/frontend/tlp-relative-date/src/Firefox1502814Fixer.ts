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

/**
 * @see https://bugzilla.mozilla.org/show_bug.cgi?id=1502814
 * @see https://jakearchibald.com/2025/firefox-custom-elements-iframes-bug/
 * @author Jake Archibald
 * */
function rescuePrototype(element: HTMLElement): void {
    if (element instanceof Firefox1502814Fixer) {
        return;
    }
    const constructor = window.customElements.get(element.tagName.toLowerCase());
    if (constructor === undefined) {
        return;
    }
    Object.setPrototypeOf(element, constructor.prototype);
}

export class Firefox1502814Fixer extends HTMLElement {
    /*
    This is called when the element is moved to a new document.
    This is where we solve the bug if the element is moved to an iframe
    without first being put into the main document.
    */
    adoptedCallback(): void {
        rescuePrototype(this);
    }

    /*
    This is called when the element is disconnected from a document.
    This happens whenever the element is moved around the DOM,
    but it also happens when the element is moved to a new document.
    This happens before adoptedCallback,
    so we need to fix it here,
    to avoid the bug in subclass disconnectedCallback calls.
    */
    disconnectedCallback(): void {
        rescuePrototype(this);
    }
}
