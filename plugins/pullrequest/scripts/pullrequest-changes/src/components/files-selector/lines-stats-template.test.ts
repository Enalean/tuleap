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
import { Option } from "@tuleap/option";
import { getLinesStatsTemplate } from "./lines-stats-template";
import type { PullRequestFile } from "../../api/rest-querier";

describe("lines-stats-template", () => {
    const renderLinesStatsTemplate = (file: PullRequestFile): HTMLElement => {
        const render = getLinesStatsTemplate(html, file);
        const root = document.implementation.createHTMLDocument().createElement("div");

        render(root, root);

        return root;
    };

    it("Given a file, then it should display the number of removed and added lines", () => {
        const lines_stats = renderLinesStatsTemplate({
            lines_added: Option.fromValue(120),
            lines_removed: Option.fromValue(0),
        } as PullRequestFile);

        const added_lines = lines_stats.querySelector("[data-test=added-lines]");
        expect(added_lines?.textContent?.trim()).toBe(`+120`);

        const removed_lines = lines_stats.querySelector("[data-test=removed-lines]");
        expect(removed_lines?.textContent?.trim()).toBe(`-0`);
    });

    it("When there are no added or removed lines, then it should display an empty string", () => {
        const lines_stats = renderLinesStatsTemplate({
            lines_added: Option.nothing(),
            lines_removed: Option.nothing(),
        } as PullRequestFile);

        expect(lines_stats.querySelector("[data-test=added-lines]")?.textContent?.trim()).toBe("");
        expect(lines_stats.querySelector("[data-test=removed-lines]")?.textContent?.trim()).toBe(
            "",
        );
    });
});
