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

interface DropdownOptions {
    keyboard?: boolean;
    dropdown_menu?: () => Element;
}
declare class Dropdown {
    constructor(trigger: Element, options?: DropdownOptions);

    toggle(): void;
    show(): void;
    hide(): void;
    addEventListener(type: string, eventHandler: (evt: Event) => void): void;
    removeEventListener(type: string, eventHandler: (evt: Event) => void): void;
}
export function dropdown(trigger: Element, options?: DropdownOptions): Dropdown;
