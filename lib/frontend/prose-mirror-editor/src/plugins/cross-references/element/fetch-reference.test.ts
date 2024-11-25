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

import { describe, it, expect, vi } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { fetchReferencesInText } from "./fetch-reference";

describe("fetch-reference", () => {
    it("Given some text and a project id, Then it should query the cross references matching the text and return them", async () => {
        const project_id = 120;
        const text = "art #123";
        const matching_references = [{ link: "https://example.com" }];
        const post = vi
            .spyOn(fetch_result, "postJSON")
            .mockReturnValue(okAsync(matching_references));
        const result = await fetchReferencesInText(text, project_id);

        if (!result.isOk()) {
            throw new Error("Expected a success");
        }

        expect(post).toHaveBeenCalledOnce();
        expect(post).toHaveBeenCalledWith(uri`/api/v1/projects/${project_id}/extract_references`, {
            text,
        });
        expect(result.value).toStrictEqual(matching_references);
    });
});
