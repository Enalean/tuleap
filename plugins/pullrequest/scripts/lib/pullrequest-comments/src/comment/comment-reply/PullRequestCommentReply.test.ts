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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import * as tooltip from "@tuleap/tooltip";
import type { HostElement } from "./PullRequestCommentReply";
import { after_render_once_descriptor } from "./PullRequestCommentReply";

vi.mock("@tuleap/tooltip", () => ({
    loadTooltips: (): void => {
        // do nothing
    },
}));

describe("PullRequestCommentReply", () => {
    let loadTooltips: SpyInstance;

    beforeEach(() => {
        loadTooltips = vi.spyOn(tooltip, "loadTooltips").mockImplementation(() => {
            // do nothing
        });
    });

    it("When the comment has rendered, then it should load its tooltips", () => {
        const host = {} as HostElement;

        after_render_once_descriptor.observe(host);

        expect(loadTooltips).toHaveBeenCalledTimes(1);
        expect(loadTooltips).toHaveBeenCalledWith(host, false);
    });
});
