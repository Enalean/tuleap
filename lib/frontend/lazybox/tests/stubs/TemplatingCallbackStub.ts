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

import type { LazyboxTemplatingCallback } from "../../src/Options";
import type { LazyboxItem } from "../../src/GroupCollection";

type ItemWithAnId = {
    readonly id: number;
};

const hasAnId = (value: unknown): value is { [K in keyof ItemWithAnId]: unknown } =>
    value !== null && typeof value === "object" && "id" in value;

const isItemWithAnId = (value: unknown): value is ItemWithAnId =>
    hasAnId(value) && typeof value.id === "number";

const getItemValue = (item: LazyboxItem): string => {
    if (isItemWithAnId(item.value)) {
        return `Value ${item.value.id}`;
    }
    return "Value ?";
};

export const TemplatingCallbackStub = {
    build: (): LazyboxTemplatingCallback => (html, item) =>
        html`<span>Badge</span>${getItemValue(item)}`,
};
