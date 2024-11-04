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

import { expect, describe, it, vi } from "vitest";
import * as create from "./create-tooltip";
import { loadTooltips } from "./load-tooltips";

describe("load-tooltips", () => {
    describe("loadTooltips", () => {
        it("should load tooltip for each supported links", () => {
            const container = document.createElement("div");
            const paragraph = document.createElement("p");

            const a_cross_reference = document.createElement("a");
            a_cross_reference.classList.add("cross-reference");
            a_cross_reference.href = "/goto?1";

            const another_cross_reference = document.createElement("a");
            another_cross_reference.classList.add("cross-reference");
            another_cross_reference.href = "/goto?2";

            const a_direct_link = document.createElement("a");
            a_direct_link.classList.add("direct-link-to-artifact");
            a_direct_link.href = "/goto?3";

            const not_supported_link = document.createElement("a");
            not_supported_link.href = "/goto?4";

            paragraph.appendChild(a_cross_reference);
            paragraph.appendChild(another_cross_reference);
            paragraph.appendChild(a_direct_link);
            paragraph.appendChild(not_supported_link);

            container.appendChild(paragraph);

            const createTooltip = vi.spyOn(create, "createTooltip");

            loadTooltips(container);

            expect(createTooltip).toHaveBeenCalledTimes(3);
            expect(createTooltip).toHaveBeenCalledWith(a_cross_reference, expect.anything());
            expect(createTooltip).toHaveBeenCalledWith(another_cross_reference, expect.anything());
            expect(createTooltip).toHaveBeenCalledWith(a_direct_link, expect.anything());
            expect(createTooltip).not.toHaveBeenCalledWith(not_supported_link, expect.anything());
        });
    });
});
