/**
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

export function clickOnElement(doc: Document, selector: string): void {
    const data_shortcut_element = doc.querySelector(selector);
    if (data_shortcut_element instanceof HTMLElement) {
        data_shortcut_element.click();
    }
}

export function focusElement(doc: Document, selector: string): void {
    const data_shortcut_element = doc.querySelector(selector);
    if (data_shortcut_element instanceof HTMLElement) {
        data_shortcut_element.focus();
    }
}
