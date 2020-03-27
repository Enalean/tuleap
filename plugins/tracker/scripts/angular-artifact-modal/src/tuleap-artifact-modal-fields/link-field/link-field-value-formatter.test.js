/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { formatLinkFieldValue } from "./link-field-value-formatter.js";

describe(`link-field-value-formatter -`, () => {
    describe(`formatLinkFieldValue()`, () => {
        it(`Given an undefined field value, then it will return null`, () => {
            const result = formatLinkFieldValue(undefined);

            expect(result).toBe(null);
        });

        it(`Given an artifact link field's value
            when its links array contains empty string, null and undefined values
            then it will filter them and return only non-null ids`, () => {
            const field_value = {
                field_id: 986,
                type: "art_link",
                permissions: ["read", "update", "create"],
                links: [{ id: "" }, { id: 202 }, { id: undefined }, { id: 584 }, { id: null }],
            };
            const result = formatLinkFieldValue(field_value);
            expect(result).toEqual({
                field_id: 986,
                links: [{ id: 202 }, { id: 584 }],
            });
        });

        it(`Given an artifact link field's value
            when the user selects a parent artifact (in the "links" array)
            and also writes links as a comma-separated string in "unformatted_links
            then it will keep only Numeric ids and concatenate them to the "links" array`, () => {
            const field_value = {
                field_id: 162,
                type: "art_link",
                permissions: ["read", "update", "create"],
                links: [{ id: 18 }],
                unformatted_links: "text,650, 673",
            };
            const result = formatLinkFieldValue(field_value);
            expect(result).toEqual({
                field_id: 162,
                links: [{ id: 18 }, { id: 650 }, { id: 673 }],
            });
        });

        it(`Given an artifact link field's value
            when its "unformatted_links" value contains a comma-separated list of ids
            then it will keep only Numeric ids and concatenate them to the "links" array`, () => {
            const field_value = {
                field_id: 919,
                type: "art_link",
                permissions: ["read", "update", "create"],
                links: [{ id: "" }],
                unformatted_links: " 551 , 404, text",
            };
            const result = formatLinkFieldValue(field_value);
            expect(result).toEqual({
                field_id: 919,
                links: [{ id: 551 }, { id: 404 }],
            });
        });
    });
});
