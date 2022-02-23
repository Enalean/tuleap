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

import type { MetadataListValue } from "./list-value-helper";
import {
    assertListIsOnlyMultipleValue,
    formatMetadataListValue,
    formatMetadataMultipleValue,
    processFormattingOnKnownType,
} from "./list-value-helper";
import type { Metadata, ListValue } from "../../../store/metadata/module";

describe("list-value-helper", () => {
    describe("assertListIsOnlyMultipleValue", () => {
        it(`returns true when first element is an object`, () => {
            const list_value: MetadataListValue = [{ id: 1, value: "multiple" } as ListValue];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(true);
        });
        it(`returns false when first element is a number`, () => {
            const list_value: MetadataListValue = [1];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(false);
        });

        it(`returns false when first element is empty`, () => {
            const list_value: MetadataListValue = [];

            expect(assertListIsOnlyMultipleValue(list_value)).toBe(false);
        });
    });

    describe("processFormattingOnKnownType", () => {
        it(`returns an array of number`, () => {
            const list_value: Array<ListValue> = [
                { id: 1, value: "multiple" } as ListValue,
                { id: 2, value: "other" } as ListValue,
            ];

            expect(processFormattingOnKnownType(list_value)).toStrictEqual([1, 2]);
        });
    });

    describe("formatMetadataMultipleValue", () => {
        it(`returns an an array with 100 when list value is null`, () => {
            const metadata: Metadata = {
                list_value: null,
            } as Metadata;

            expect(formatMetadataMultipleValue(metadata)).toStrictEqual([100]);
        });

        it(`returns an an array with 100 when list value is a single list`, () => {
            const list_value: MetadataListValue = [1];

            const metadata: Metadata = {
                list_value,
            } as Metadata;

            expect(formatMetadataMultipleValue(metadata)).toStrictEqual([100]);
        });

        it(`returns formatted metadata`, () => {
            const list_value: MetadataListValue = [
                { id: 1, value: "multiple" } as ListValue,
                { id: 2, value: "other" } as ListValue,
            ];

            const metadata: Metadata = {
                list_value,
            } as Metadata;

            expect(formatMetadataMultipleValue(metadata)).toStrictEqual([1, 2]);
        });
    });

    describe("formatMetadataListValue", () => {
        it(`returns none value (100) when list value is null`, () => {
            const metadata: Metadata = {
                list_value: null,
            } as Metadata;

            expect(formatMetadataListValue(metadata)).toStrictEqual(100);
        });

        it(`returns none value (100) when list value is malformed`, () => {
            const list_value: MetadataListValue = [1];

            const metadata: Metadata = {
                list_value,
            } as Metadata;

            expect(formatMetadataListValue(metadata)).toStrictEqual(100);
        });

        it(`returns formatted metadata`, () => {
            const list_value: Array<ListValue> = [{ id: 1, value: "single" } as ListValue];

            const metadata: Metadata = {
                list_value,
            } as Metadata;

            expect(formatMetadataListValue(metadata)).toStrictEqual(1);
        });
    });
});
