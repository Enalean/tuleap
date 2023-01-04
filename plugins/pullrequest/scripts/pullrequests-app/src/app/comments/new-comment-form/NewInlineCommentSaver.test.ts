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

import * as tuleap_api from "@tuleap/fetch-result";
import { NewInlineCommentContext } from "./NewInlineCommentContext";
import { INLINE_COMMENT_POSITION_RIGHT } from "../types";
import { NewInlineCommentSaver } from "./NewInlineCommentSaver";
import { uri } from "@tuleap/fetch-result";

describe("NewInlineCommentSaver", () => {
    it("should save the new inline comment", () => {
        const postSpy = jest.spyOn(tuleap_api, "postJSON").mockImplementation();
        const comment_saver = NewInlineCommentSaver(
            NewInlineCommentContext.fromContext(1, "README.md", 55, INLINE_COMMENT_POSITION_RIGHT)
        );

        comment_saver.postComment("Noice!");

        expect(postSpy).toHaveBeenCalledWith(uri`/api/v1/pull_requests/1/inline-comments`, {
            file_path: "README.md",
            unidiff_offset: 55,
            position: INLINE_COMMENT_POSITION_RIGHT,
            content: "Noice!",
        });
    });
});
