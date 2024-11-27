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

import { describe, it, expect } from "vitest";
import {
    match_all_references_regexp,
    match_newly_typed_reference_regexp,
    match_single_reference_regexp,
} from "./regexps";

const valid_references = [
    "art #123",
    "art #abc",
    "art #abc:123",
    "art #123:123",
    "art #abc:abc",
    "art #123:wikipage/2",
    "art #abc-def:ghi",
    "art #abc-de_f:ghi",
    "ref #12784.",
    "ref #a.b-c_d/12784.",
];

const invalid_references = ["art 123", "art#123", "art #", "something"];

describe("regexps", () => {
    describe("match_all_references_regexp", () => {
        it("Given some text, Then it should match all the parts matching the Tuleap reference format", () => {
            const text_with_references = `
                This text references:
                ${valid_references.map((ref) => `- ${ref}`)}
            `;

            const matches = text_with_references.matchAll(match_all_references_regexp);
            expect(Array.from(matches)).toHaveLength(valid_references.length);

            for (const match of matches) {
                expect(valid_references.includes(match[0])).toBe(true);
            }
        });

        it("Given some text, Then it should match all the parts matching the Tuleap reference format", () => {
            const text_with_invalid_references = `
                This text references:
                ${invalid_references.map((ref) => `- ${ref}`)}
            `;

            const matches = text_with_invalid_references.matchAll(match_all_references_regexp);
            expect(Array.from(matches)).toHaveLength(0);
        });
    });

    describe("match_single_reference_regexp", () => {
        it("Given some text containing a valid Tuleap reference, Then it should match it", () => {
            valid_references.forEach((reference) => {
                const match = match_single_reference_regexp.exec(
                    `This text references ${reference}`,
                );
                if (!match) {
                    throw new Error("Expected a match");
                }
                expect(match[0]).toBe(reference);
            });
        });

        it("Given some text containing an invalid Tuleap reference, Then it should not match it", () => {
            invalid_references.forEach((reference) => {
                expect(
                    match_single_reference_regexp.test(`This text references ${reference}`),
                ).toBe(false);
            });
        });
    });

    describe("match_newly_typed_reference_regexp", () => {
        it("Given some text ending with a Tuleap reference and a space character, then it should match it", () => {
            valid_references.forEach((reference) => {
                const match = match_newly_typed_reference_regexp.exec(
                    `This text references ${reference} `,
                );
                if (!match) {
                    throw new Error("Expected a match");
                }
                expect(match[0]).toBe(`${reference} `);
            });
        });

        it("Given some text ending with a Tuleap reference but without a space character, then it should not match it", () => {
            valid_references.forEach((reference) => {
                expect(
                    match_newly_typed_reference_regexp.test(`This text references ${reference}`),
                ).toBe(false);
            });
        });

        it("Given some text ending with an invalid reference and a space character, then it should not match it", () => {
            invalid_references.forEach((reference) => {
                expect(
                    match_newly_typed_reference_regexp.test(`This text references ${reference} `),
                ).toBe(false);
            });
        });
    });
});
