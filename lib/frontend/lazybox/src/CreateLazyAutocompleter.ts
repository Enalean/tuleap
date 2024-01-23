/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { TAG } from "./LazyAutocompleterElement";
import type { LazyAutocompleter } from "./LazyAutocompleterElement";

const isLazyAutocompleter = (element: HTMLElement): element is LazyAutocompleter & HTMLElement =>
    element.tagName === TAG.toUpperCase();

/**
 * Returns a new LazyAutocompleter element.
 * You should then configure it by setting the `options` property and add it to the document.
 * @param doc Document The Document in which to create the element.
 */
export const createLazyAutocompleter = (doc: Document): LazyAutocompleter & HTMLElement => {
    const element = doc.createElement(TAG);
    if (!isLazyAutocompleter(element)) {
        throw Error("Could not create LazyAutocompleter element");
    }
    return element;
};
