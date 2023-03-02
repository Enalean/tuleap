/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { initializeMermaid } from "./initialize-mermaid";
import * as mermaid from "mermaid";

vi.mock("mermaid", () => {
    return {
        default: {
            initialize: vi.fn(),
        },
    };
});

describe("initializeMermaid", () => {
    it("initializes Mermaid only once", () => {
        const initialize = vi.spyOn(mermaid.default, "initialize");

        initializeMermaid();
        initializeMermaid();

        expect(initialize).toHaveBeenCalledTimes(1);
        expect(initialize).toHaveBeenCalledWith({
            startOnLoad: false,
            securityLevel: "strict",
            theme: "default",
            flowchart: {
                htmlLabels: false,
            },
            secure: [
                "secure",
                "securityLevel",
                "startOnLoad",
                "maxTextSize",
                "theme",
                "fontFamily",
            ],
        });
    });
});
