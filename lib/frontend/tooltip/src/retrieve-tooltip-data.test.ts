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

import { expect, describe, it, vi, beforeEach } from "vitest";
import { retrieveTooltipData } from "./retrieve-tooltip-data";

describe("retrieve-tooltip-data", () => {
    const fetch = vi.fn();

    beforeEach(() => {
        vi.resetAllMocks();
        vi.stubGlobal("fetch", fetch);
    });

    it("should ask for json content", async () => {
        fetch.mockResolvedValue({
            ok: true,
            headers: new Headers({ "Content-Type": "application/json" }),
            json: () => new Promise((resolve) => resolve("le content")),
        });

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(fetch).toHaveBeenCalledWith(
            "https://example.com/goto?key=art&value=123&as-json-for-tooltip=1",
            {
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
            },
        );
        expect(result).toBe("le content");
    });

    it("may receive text content", async () => {
        fetch.mockResolvedValue({
            ok: true,
            headers: new Headers({ "Content-Type": "text/plain" }),
            text: () => new Promise((resolve) => resolve("le content")),
        });

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBe("le content");
    });

    it("returns undefined if response does not send content-type headers", async () => {
        fetch.mockResolvedValue({
            ok: true,
            headers: new Headers({}),
        });

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBeUndefined();
    });

    it("returns undefined if response is not a success", async () => {
        fetch.mockResolvedValue({
            ok: false,
        });

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBeUndefined();
    });
});
