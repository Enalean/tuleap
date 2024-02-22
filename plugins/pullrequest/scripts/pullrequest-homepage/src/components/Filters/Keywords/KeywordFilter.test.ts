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
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { KeywordFilterBuilder, TYPE_FILTER_KEYWORD } from "./KeywordFilter";

describe("KeywordFilter", () => {
    it("Given a keyword and an id, then it should create a KeywordFilter", () => {
        const keyword = "security";
        const keyword_id = 1;
        const filter = KeywordFilterBuilder(GettextStub).fromKeyword(keyword_id, keyword);

        expect(filter.id).toBe(keyword_id);
        expect(filter.type).toBe(TYPE_FILTER_KEYWORD);
        expect(filter.label).toBe(`Keyword: ${keyword}`);
        expect(filter.value).toBe(keyword);
        expect(filter.is_unique).toBe(false);
    });
});
