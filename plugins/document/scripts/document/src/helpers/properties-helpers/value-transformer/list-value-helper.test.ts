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

import { describe, expect, it } from "vitest";
import type { PropertyListValue } from "./list-value-helper";
import {
    assertListIsOnlyMultipleValue,
    formatPropertyListValue,
    formatPropertyListMultipleValue,
    processFormattingOnKnownType,
} from "./list-value-helper";
import type { Property, ListValue } from "../../../type";

describe("list-value-helper", () => {
    describe("assertListIsOnlyMultipleValue", () => {
        it(`returns true when first element is an object`, () => {
            const list_value: PropertyListValue = [{ id: 1, name: "multiple" } as ListValue];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(true);
        });
        it(`returns false when first element is a number`, () => {
            const list_value: PropertyListValue = [1];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(false);
        });

        it(`returns false when first element is empty`, () => {
            const list_value: PropertyListValue = [];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(false);
        });
    });

    describe("processFormattingOnKnownType", () => {
        it(`returns an array of number`, () => {
            const list_value: Array<ListValue> = [
                { id: 1, name: "multiple" } as ListValue,
                { id: 2, name: "other" } as ListValue,
            ];

            expect(processFormattingOnKnownType(list_value)).toStrictEqual([1, 2]);
        });
    });

    describe("formatPropertyListMultipleValue", () => {
        it(`returns an an array with 100 when list value is null`, () => {
            const property: Property = {
                list_value: null,
            } as Property;

            expect(formatPropertyListMultipleValue(property)).toStrictEqual([100]);
        });

        it(`returns an an array with 100 when list value is a single list`, () => {
            const list_value: PropertyListValue = [1];

            const property: Property = {
                list_value,
            } as Property;

            expect(formatPropertyListMultipleValue(property)).toStrictEqual([100]);
        });

        it(`returns formatted property`, () => {
            const list_value: PropertyListValue = [
                { id: 1, name: "multiple" } as ListValue,
                { id: 2, name: "other" } as ListValue,
            ];

            const property: Property = {
                list_value,
            } as Property;

            expect(formatPropertyListMultipleValue(property)).toStrictEqual([1, 2]);
        });
    });

    describe("formatPropertyListValue", () => {
        it(`returns none value (100) when list value is null`, () => {
            const property: Property = {
                list_value: null,
            } as Property;

            expect(formatPropertyListValue(property)).toBe(100);
        });

        it(`returns none value (100) when list value is malformed`, () => {
            const list_value: PropertyListValue = [1];

            const property: Property = {
                list_value,
            } as Property;

            expect(formatPropertyListValue(property)).toBe(100);
        });

        it(`returns formatted property`, () => {
            const list_value: Array<ListValue> = [{ id: 1, name: "single" } as ListValue];

            const property: Property = {
                list_value,
            } as Property;

            expect(formatPropertyListValue(property)).toBe(1);
        });
    });
});
