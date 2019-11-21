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

export function createElement(...css_classes: string[]): HTMLElement {
    const local_document = document.implementation.createHTMLDocument();
    const div = local_document.createElement("div");
    div.classList.add(...css_classes);
    return div;
}

export function createNonHTMLElement(): Element {
    const local_document = document.implementation.createDocument(
        "http://www.w3.org/2000/svg",
        "svg",
        null
    );
    return local_document.createElement("g");
}
