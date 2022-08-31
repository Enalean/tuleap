/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { extractNextUrl } from "./link-header-helper";

describe("link-header-helper", () => {
    describe("getNextUrl", () => {
        it("Given a link header, then it should extract the next url", () => {
            const link_header = `
                <https://example.com/api/stuff?page=2&per_page=50>; rel="next",
                <https://example.com/api/stuff?page=1&per_page=50>; rel="first",
                <https://example.com/api/stuff?page=2&per_page=50>; rel="last"
            `;

            const next_url = extractNextUrl(link_header);

            expect(next_url).toBe("https://example.com/api/stuff?page=2&per_page=50");
        });

        it("Returns null when no next url is found", () => {
            const link_header = `
                <https://example.com/api/stuff?page=1&per_page=50>; rel="first",
                <https://example.com/api/stuff?page=2&per_page=50>; rel="last"
            `;

            const next_url = extractNextUrl(link_header);

            expect(next_url).toBeNull();
        });
    });
});
