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
import { PullRequestCommentPresenterStub } from "../../../tests/stubs/PullRequestCommentPresenterStub";
import type { InternalEditionForm } from "./EditionForm";
import { after_render_once_descriptor } from "./EditionForm";

describe("EditionForm", () => {
    it("When the EditionForm has been rendered once, Then its writing zone should be initialized with the current comment raw_content", () => {
        const raw_content = "Part of request #123";
        const doc = document.implementation.createHTMLDocument();
        const setWritingZoneContent = vi.fn();
        const host = {
            writing_zone_controller: {
                setWritingZoneContent,
            },
            writing_zone: doc.createElement("div"),
            comment: PullRequestCommentPresenterStub.buildGlobalCommentWithData({
                raw_content,
            }),
        } as unknown as InternalEditionForm;

        after_render_once_descriptor.observe(host);

        expect(setWritingZoneContent).toHaveBeenCalledOnce();
        expect(setWritingZoneContent).toHaveBeenCalledWith(host.writing_zone, raw_content);
    });
});
