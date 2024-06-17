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

import { describe, it, expect } from "vitest";
import type { HostElement } from "./PullRequestCommentSkeleton";
import { renderSkeleton } from "./PullRequestCommentSkeleton";

describe("PullRequestCommentSkeleton", () => {
    const renderComponent = (has_replies: boolean): ShadowRoot => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        const host = { has_replies } as HostElement;
        const render = renderSkeleton(host);
        render(host, target);

        return target;
    };

    it("should not display the follow ups section when has_replies is false", () => {
        expect(
            renderComponent(false).querySelector("[data-test=skeleton-follow-ups-section]"),
        ).toBeNull();
    });

    it("should  display the follow ups section when has_replies is true", () => {
        expect(
            renderComponent(true).querySelector("[data-test=skeleton-follow-ups-section]"),
        ).not.toBeNull();
    });
});
