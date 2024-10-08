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

import { describe, it, expect } from "vitest";
import {
    createCrossReferenceDecoration,
    CROSS_REFERENCE_DECORATION_TYPE,
} from "./create-cross-reference-decoration";

describe("create-cross-reference-decoration", () => {
    it("Given a position and a CrossReference, Then it should return a CrossReference decoration", () => {
        const reference_position = { from: 10, to: 18 };

        const decoration = createCrossReferenceDecoration(reference_position, {
            text: "art #123",
            link: "https://example.com",
            context: "text",
        });

        expect(decoration.from).toBe(reference_position.from);
        expect(decoration.to).toBe(reference_position.to);
        expect(decoration.spec).toStrictEqual({
            type: CROSS_REFERENCE_DECORATION_TYPE,
        });
    });
});
