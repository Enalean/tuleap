/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { createLocalDocument } from "../../../helpers";
import { displayFullyLoaded, displayLoading, resetState } from "./display-state";

describe("display-state", () => {
    let host: Element;

    beforeEach(() => {
        host = createLocalDocument().createElement("span");
        host.classList.add("cross-reference", "cross-reference-loading");
    });

    it("resetState() should remove the .cross-reference and the .cross-reference-loading classes from host", () => {
        resetState(host);
        expect(host.classList.length).toBe(0);
    });

    it("displayFullyLoaded() should make host have only the .cross-reference class", () => {
        displayFullyLoaded(host);
        expect(host.classList.length).toBe(1);
        expect(host.classList.contains("cross-reference")).toBe(true);
    });

    it("displayLoading() should make host have only the .cross-reference-loading class", () => {
        displayLoading(host);
        expect(host.classList.length).toBe(1);
        expect(host.classList.contains("cross-reference-loading")).toBe(true);
    });
});
