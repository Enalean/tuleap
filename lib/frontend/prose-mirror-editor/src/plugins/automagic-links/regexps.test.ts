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
import { match_newly_typed_https_url_regexp, match_single_https_url_regexp } from "./regexps";

const valid_urls = [
    "https://example.com",
    "https://example.com:443",
    "https://www.example.com#/app/",
    "https://www.example.com?query=abcd&stuff_id=123",
    "https://example.com/a?a[0]=a",
    "https://é.example.com",
    "https://العربية.example.com/",
    "https://127.0.0.1",
    "https://127.0.0.1:8080",
    "https://127.0.0.1/index.php",
    "https://example.example",
];

const invalid_urls = [
    "example.com",
    "mailto:john.doe@example.com",
    "http://www.example.com",
    "www.example.com",
    "ftp://ftp.example.com/file",
    "news.us.example.com",
];

describe("regexps", () => {
    describe("match_single_https_url_regexp", () => {
        it("Given some text containing a valid https url, Then it should match it", () => {
            valid_urls.forEach((url) => {
                const match = match_single_https_url_regexp.exec(
                    `This text has an url in it: ${url}`,
                );
                if (!match) {
                    throw new Error("Expected a match");
                }
                expect(match[0]).toBe(url);
            });
        });

        it("Given some text containing an invalid url, Then it should match it", () => {
            invalid_urls.forEach((url) => {
                expect(
                    match_single_https_url_regexp.test(`This text has an url in it: ${url}`),
                ).toBe(false);
            });
        });
    });

    describe("match_newly_typed_https_url_regexp", () => {
        it("Given some text ending with a valid https url and a space character, then it should match it", () => {
            valid_urls.forEach((url) => {
                const match = match_newly_typed_https_url_regexp.exec(
                    `This text has an url in it: ${url} `,
                );
                if (!match) {
                    throw new Error("Expected a match");
                }
                expect(match[0]).toBe(`${url} `);
            });
        });

        it("Given some text ending with a valid https url but without a space character, then it should not match it", () => {
            valid_urls.forEach((url) => {
                expect(
                    match_newly_typed_https_url_regexp.test(`This text has an url in it: ${url}`),
                ).toBe(false);
            });
        });

        it("Given some text ending with an invalid url and a space character, then it should not match it", () => {
            invalid_urls.forEach((url) => {
                expect(
                    match_newly_typed_https_url_regexp.test(`This text has an url in it: ${url} `),
                ).toBe(false);
            });
        });
    });
});
