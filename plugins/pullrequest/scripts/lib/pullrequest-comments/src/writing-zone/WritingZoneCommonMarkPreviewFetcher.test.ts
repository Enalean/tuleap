/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { fetchCommonMarkPreview } from "./WritingZoneCommonMarkPreviewFetcher";

const project_id = 105;

describe("WritingZoneCommonMarkPreviewFetcher", () => {
    it("should fetch the endpoint and return the previewed content", async () => {
        const interpreted_commonmark = "<p>some commonmark</p>";
        const post_spy = vi
            .spyOn(fetch_result, "postFormWithTextResponse")
            .mockReturnValue(okAsync(interpreted_commonmark));
        const result = await fetchCommonMarkPreview(project_id, "some commonmark");

        if (!result.isOk()) {
            throw new Error("Expected an Ok");
        }

        expect(post_spy).toHaveBeenCalledWith(
            uri`/project/${project_id}/interpret-commonmark`,
            expect.any(Object)
        );
        expect(result.value).toBe(interpreted_commonmark);
    });
});
