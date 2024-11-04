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
import { retrieveTooltipData } from "./retrieve-tooltip-data";

describe("retrieve-tooltip-data", () => {
    it("should ask for json content", async () => {
        const mocked = vi.fn();
        mocked.mockResolvedValue({
            ok: true,
            headers: new Headers({ "Content-Type": "application/json" }),
            json: () => new Promise((resolve) => resolve("le content")),
        });
        global.fetch = mocked;

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(global.fetch).toHaveBeenCalledWith(
            "https://example.com/goto?key=art&value=123&as-json-for-tooltip=1",
            {
                credentials: "same-origin",
                headers: { "X-Requested-With": "XMLHttpRequest" },
            },
        );
        expect(result).toBe("le content");
    });

    it("may receive text content", async () => {
        const mocked = vi.fn();
        mocked.mockResolvedValue({
            ok: true,
            headers: new Headers({ "Content-Type": "text/plain" }),
            text: () => new Promise((resolve) => resolve("le content")),
        });
        global.fetch = mocked;

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBe("le content");
    });

    it("returns undefined if response does not send content-type headers", async () => {
        const mocked = vi.fn();
        mocked.mockResolvedValue({
            ok: true,
            headers: new Headers({}),
        });
        global.fetch = mocked;

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBeUndefined();
    });

    it("returns undefined if response is not a success", async () => {
        const mocked = vi.fn();
        mocked.mockResolvedValue({
            ok: false,
        });
        global.fetch = mocked;

        const result = await retrieveTooltipData(
            new URL("https://example.com/goto?key=art&value=123"),
        );

        expect(result).toBeUndefined();
    });
});
