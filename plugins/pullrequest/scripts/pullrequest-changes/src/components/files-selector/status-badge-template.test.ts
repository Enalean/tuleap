/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
import { html } from "@tuleap/lazybox";
import {
    FILE_STATUS_ADDED,
    FILE_STATUS_DELETED,
    FILE_STATUS_MODIFIED,
    type PullRequestFile,
} from "../../api/rest-querier";
import { getStatusBadgeTemplate } from "./status-badge-template";

describe("status-badge-template", () => {
    it.each([
        [FILE_STATUS_ADDED, "pull-request-file-status-added"],
        [FILE_STATUS_MODIFIED, "pull-request-file-status-modified"],
        [FILE_STATUS_DELETED, "pull-request-file-status-deleted"],
    ])(
        "When the file status is %s, then it should have the .%s class.",
        (status, expected_class) => {
            const file = { status } as PullRequestFile;
            const render = getStatusBadgeTemplate(html, file);
            const root = document.implementation.createHTMLDocument().createElement("div");

            render(root, root);

            const status_element = root.querySelector("[data-test=file-status]");
            expect(status_element?.textContent?.trim()).toBe(status);
            expect(status_element?.classList).toContain(expected_class);
        },
    );
});
