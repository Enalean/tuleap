/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * Read the value of element's dataset at the given key. If the element has no dataset
 * (no data-${key} attribute for example), it throws an error.
 * @param element The element on which to read the data-attribute or dataset.
 * @param key The name of the data-attribute or dataset key. Must be in camelCase format.
 * @returns The value of the data-attribute for the given key.
 * @throws Error
 */
export const getDatasetItemOrThrow = (element: HTMLElement, key: string): string => {
    const value = element.dataset[key];
    if (value === undefined) {
        throw Error(`Missing item ${key} in dataset`);
    }
    return value;
};

type Queryable = Pick<ParentNode, "querySelector">;
type Constructor<TypeOfElement> = new () => TypeOfElement;

/**
 * Select an element descendant of base with Element.querySelector(). If the element
 * is not an instance of element_constructor, it throws an error.
 * @param base Element or Document that is the ancestor of the element to select.
 * @param selectors Valid selectors for Element.querySelector().
 * @param element_constructor Constructor for the interface the selected element must be an
 * instance of. Defaults to HTMLElement.
 * @returns The selected element.
 * @throws Error
 */
export function selectOrThrow<TypeOfElement extends HTMLElement>(
    base: Queryable,
    selectors: string,
    element_constructor: Constructor<TypeOfElement>,
): TypeOfElement;
export function selectOrThrow(base: Queryable, selectors: string): HTMLElement;
export function selectOrThrow( // eslint-disable-line @typescript-eslint/explicit-function-return-type
    base: Queryable,
    selectors: string,
    element_constructor = HTMLElement,
) {
    const element = base.querySelector(selectors);
    if (!(element instanceof element_constructor)) {
        throw Error(`Could not find element with selector '${selectors}'`);
    }
    return element;
}
