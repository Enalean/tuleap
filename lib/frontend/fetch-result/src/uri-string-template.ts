/**
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

const encoded_uri_symbol = Symbol("encoded_uri");
export interface EncodedURI {
    readonly [encoded_uri_symbol]: string;
}

function encode(value: string | number | boolean | EncodedURI): string {
    if (typeof value !== "object") {
        return encodeURIComponent(value);
    }

    return value[encoded_uri_symbol];
}
export const uri = (
    strings: TemplateStringsArray,
    ...keys: ReadonlyArray<string | boolean | number | EncodedURI>
): EncodedURI => {
    let encoded_uri = "";
    for (let index = 0; index < strings.length - 1; index++) {
        encoded_uri += strings[index] + encode(keys[index]);
    }
    encoded_uri += strings[strings.length - 1];
    return { [encoded_uri_symbol]: encoded_uri };
};

export const rawUri = (value: string | number | boolean): EncodedURI => {
    return { [encoded_uri_symbol]: String(value) };
};

export function getEncodedURIString(value: EncodedURI): string {
    return value[encoded_uri_symbol];
}
