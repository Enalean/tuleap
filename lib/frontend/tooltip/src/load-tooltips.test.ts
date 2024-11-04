/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { beforeEach, expect, describe, it, vi } from "vitest";
import type { MockInstance } from "vitest";
import * as create from "./create-tooltip";
import { loadTooltipOnAnchorElement, loadTooltips } from "./load-tooltips";

describe("load-tooltips", () => {
    let createTooltip: MockInstance;
    beforeEach(() => {
        createTooltip = vi.spyOn(create, "createTooltip");
    });

    function expectCreateTooltipToHaveBeenCalledWith(
        calls: Array<{ element: HTMLElement; href: string }>,
    ): void {
        expect(createTooltip).toHaveBeenCalledTimes(calls.length);
        calls.forEach(({ element, href }, index) => {
            expect(createTooltip.mock.calls[index][0].element).toStrictEqual(element);
            expect(createTooltip.mock.calls[index][0].getHref()).toStrictEqual(href);
        });
    }

    describe("loadTooltips", () => {
        it("should load tooltip for each supported links", () => {
            const container = document.createElement("div");
            const paragraph = document.createElement("p");

            const a_cross_reference = document.createElement("a");
            a_cross_reference.classList.add("cross-reference");
            a_cross_reference.href = "https://example.com/goto?1";

            const another_cross_reference = document.createElement("a");
            another_cross_reference.classList.add("cross-reference");
            another_cross_reference.href = "https://example.com/goto?2";

            const a_direct_link = document.createElement("a");
            a_direct_link.classList.add("direct-link-to-artifact");
            a_direct_link.href = "https://example.com/goto?3";

            const not_supported_link = document.createElement("a");
            not_supported_link.href = "https://example.com/goto?4";

            const span_with_data_href = document.createElement("span");
            span_with_data_href.classList.add("cross-reference");
            span_with_data_href.dataset.href = "https://example.com/goto?5";

            paragraph.appendChild(a_cross_reference);
            paragraph.appendChild(another_cross_reference);
            paragraph.appendChild(a_direct_link);
            paragraph.appendChild(not_supported_link);
            paragraph.appendChild(span_with_data_href);

            container.appendChild(paragraph);

            loadTooltips(container);

            expectCreateTooltipToHaveBeenCalledWith([
                {
                    element: a_cross_reference,
                    href: "https://example.com/goto?1",
                },
                {
                    element: another_cross_reference,
                    href: "https://example.com/goto?2",
                },
                {
                    element: a_direct_link,
                    href: "https://example.com/goto?3",
                },
                {
                    element: span_with_data_href,
                    href: "https://example.com/goto?5",
                },
            ]);
        });

        it("should destroy existing tooltips", () => {
            const container = document.createElement("div");

            const a_cross_reference = document.createElement("a");
            a_cross_reference.classList.add("cross-reference");
            a_cross_reference.href = "https://example.com/goto?1";

            container.appendChild(a_cross_reference);

            const destroy = vi.fn();

            const createTooltip = vi.spyOn(create, "createTooltip");
            createTooltip.mockReturnValue({
                destroy,
            });

            loadTooltips(container);

            expect(destroy).not.toHaveBeenCalled();

            loadTooltips(container);

            expect(destroy).toHaveBeenCalled();
        });
    });

    describe("loadTooltipOnAnchorElement", () => {
        it("should load tooltip on a given HTMLAnchorElement", () => {
            const a_cross_reference = document.createElement("a");
            a_cross_reference.classList.add("cross-reference");
            a_cross_reference.href = "https://example.com/goto?1";

            loadTooltipOnAnchorElement(a_cross_reference);

            expectCreateTooltipToHaveBeenCalledWith([
                {
                    element: a_cross_reference,
                    href: "https://example.com/goto?1",
                },
            ]);
        });
    });
});
