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
 *
 */

import { expect, describe, it } from "vitest";
import type { CrossReference } from "../reference-extractor";
import { ContextLengthComputer } from "./ContextLengthPositionComputer";

describe("ContextLengthComputer", () => {
    it("It returns context length + space character", () => {
        const reference: CrossReference = {
            text: "art #123",
            link: "https://example.com",
            context: "test",
        };

        expect(ContextLengthComputer().computeContextLength(reference)).toEqual(5);
    });

    it("It returns 0 when reference has no context", () => {
        const reference: CrossReference = {
            text: "art #123",
            link: "https://example.com",
            context: "",
        };

        expect(ContextLengthComputer().computeContextLength(reference)).toEqual(0);
    });
});
