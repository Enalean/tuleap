/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { TAG } from "./LazyboxElement";
import type { Lazybox } from "./LazyboxElement";

const isLazybox = (element: HTMLElement): element is Lazybox & HTMLElement =>
    element.tagName === TAG.toUpperCase();

/**
 * Returns a new Lazybox element.
 * You should then configure it by setting the `options` property and add it to the document.
 * @param doc Document The Document in which to create the element.
 */
export const createLazybox = (doc: Document): Lazybox & HTMLElement => {
    const element = doc.createElement(TAG);
    if (!isLazybox(element)) {
        throw Error("Could not create Lazybox element");
    }
    return element;
};
