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

import { describe, it, expect } from "vitest";
import { getURI } from "./auto-encoder";
import { uri as uriTag } from "./uri-string-template";

describe(`auto-encoder`, () => {
    describe(`getURI`, () => {
        const uri = uriTag`https://example.com/auto-encoder-test`;

        it(`given a base URI and an object containing key/value pairs for URI parameters,
            it will URI-encode them and append them to the base URI`, () => {
            const params = {
                quinonyl: "mem",
                "R&D": 91,
                Jwahar: false,
            };

            const encoded_uri = getURI(uri, params);
            expect(encoded_uri).toStrictEqual(
                uriTag`https://example.com/auto-encoder-test?quinonyl=mem&R%26D=91&Jwahar=false`,
            );
        });

        it(`given an absolute URI with implicit protocol and domain-name, it will accept it`, () => {
            const uri = uriTag`/api/v1/artifacts/123`;
            expect(getURI(uri)).toStrictEqual(uri);
        });

        it(`given a base URI and empty params, it will return the base URI`, () => {
            expect(getURI(uri, {})).toStrictEqual(uri);
        });

        it(`defaults params to an empty object`, () => {
            expect(getURI(uri)).toStrictEqual(uri);
        });
    });
});
