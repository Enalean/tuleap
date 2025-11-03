/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { getItemReferences } from "./references-rest-querier";

describe("references-rest-querier", () => {
    describe("getItemReferences", () => {
        it("should returns the result in an okAsync", async () => {
            const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(
                okAsync({
                    sources_by_nature: [],
                    targets_by_nature: [],
                    has_source: false,
                    has_target: false,
                }),
            );

            const result = await getItemReferences(123, 102);

            expect(getJSON).toHaveBeenCalledWith(
                uri`/project/102/cross-references/123?type=document`,
            );
            expect(result.isOk()).toBe(true);
        });
    });
});
