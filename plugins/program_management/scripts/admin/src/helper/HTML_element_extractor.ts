/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

export function getHTMLSelectElementFromId(doc: Document, id: string): HTMLSelectElement {
    const select_element = doc.getElementById(id);

    if (!select_element || !(select_element instanceof HTMLSelectElement)) {
        throw new Error(id + " element does not exist");
    }

    return select_element;
}

export function getHTMLInputElementFromId(doc: Document, element_id: string): HTMLInputElement {
    const input_element = doc.getElementById(element_id);

    if (!input_element || !(input_element instanceof HTMLInputElement)) {
        throw new Error("No " + element_id + " input element");
    }

    return input_element;
}
