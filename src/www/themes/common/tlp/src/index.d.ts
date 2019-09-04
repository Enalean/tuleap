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

interface FetchWrapperParameter {
    [key: string]: string | number;
}
export function get(
    url: string,
    init?: RequestInit & { method?: "GET"; params?: FetchWrapperParameter }
): Promise<Response>;
interface RecursiveGetLimitParameters {
    limit?: number;
    offset?: number;
}
interface RecursiveGetInit {
    params?: FetchWrapperParameter & RecursiveGetLimitParameters;
    getCollectionCallback?: (json: Array<any>) => Array<any>;
}
export function recursiveGet(url: string, init?: RecursiveGetInit): Promise<Array<any>>;
export function put(url: string, init?: RequestInit & { method?: "PUT" }): Promise<Response>;
export function patch(url: string, init?: RequestInit & { method?: "PATCH" }): Promise<Response>;
export function post(url: string, init?: RequestInit & { method?: "POST" }): Promise<Response>;
export function del(url: string, init?: RequestInit & { method?: "DELETE" }): Promise<Response>;
export function options(
    url: string,
    init?: RequestInit & { method?: "OPTIONS" }
): Promise<Response>;

interface ModalOptions {
    keyboard?: boolean;
    destroy_on_hide?: boolean;
}
declare class Modal {
    constructor(element: Element, options?: ModalOptions);

    toggle(): void;
    show(): void;
    hide(): void;
    destroy(): void;
    addEventListener(type: string, listener: (evt: Event) => void): void;
    removeEventListener(type: string, listener: (evt: Event) => void): void;
}
export function modal(element: Element, options?: ModalOptions): Modal;

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

import { PopperOptions } from "popper.js";
interface Popover {
    destroy(): void;
}
export function createPopover(
    popover_trigger: Element,
    popover_content: Element,
    options?: PopperOptions & { anchor?: Element }
): Popover;

interface FilterTable {
    filterTable(): void;
}
export function filterInlineTable(filter: Element): FilterTable;

import { OptionData, Select2Plugin } from "select2";
export function select2(element: Element, options?: OptionData): Select2Plugin;

export function datePicker(element: Element, options?: object): object;

export as namespace tlp;
