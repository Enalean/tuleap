/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import { loadImage } from "./image-loader";
import * as tlp_fetch from "@tuleap/tlp-fetch";

vi.mock("@tuleap/tlp-fetch");

describe("image-loader", () => {
    it("loads an image", () => {
        Object.defineProperty(global.Image.prototype, "decode", {
            get() {
                return (): Promise<void> => Promise.resolve();
            },
        });
        global.URL.createObjectURL = (): string => "some url";
        global.URL.revokeObjectURL = (): void => {
            // nothing
        };
        global.Blob.prototype.arrayBuffer = (): Promise<ArrayBuffer> =>
            Promise.resolve(new ArrayBuffer(1));

        const get_spy = vi.spyOn(tlp_fetch, "get");
        get_spy.mockResolvedValue({
            blob: () => Promise.resolve(new Blob()),
        } as Response);

        expect(() => {
            return loadImage("/example.png");
        }).not.toThrow();
    });
});
