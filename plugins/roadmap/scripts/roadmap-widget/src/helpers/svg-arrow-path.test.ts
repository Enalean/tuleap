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

import {
    getDownLeftArrow,
    getDownRightArrow,
    getUpLeftArrow,
    getUpRightArrow,
} from "./svg-arrow-path";
import { threshold } from "./path";
import removeExtraWhitespaces from "./remove-extra-whitespaces";

describe("svg-arrow-path", () => {
    describe("getDownRightArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getDownRightArrow(100, 100))).toMatchInlineSnapshot(
                `"M24 24 L32 24 Q40 24, 40 32 L40 68 Q40 76, 48 76 L76 76 L68 68 M76 76 L68 84"`
            );
        });

        it("Zigzags if width is lesser than thresold", () => {
            expect(
                removeExtraWhitespaces(getDownRightArrow(threshold - 10, 100))
            ).toMatchInlineSnapshot(
                `"M24 24 L32 24 Q40 24, 40 32 L40 36 Q40 44, 32 44 L16 44 Q8 44, 8 52 L8 68 Q8 76, 16 76 L46 76 L38 68 M46 76 L38 84"`
            );
        });
    });

    describe("getDownLeftArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getDownLeftArrow(100, 100))).toMatchInlineSnapshot(
                `"M76 24 L84 24 Q92 24, 92 32 L92 36 Q92 44, 84 44 L16 44 Q8 44, 8 52 L8 68 Q8 76, 16 76 L24 76 L16 68 M24 76 L16 84"`
            );
        });
    });

    describe("getUpRightArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getUpRightArrow(100, 100))).toMatchInlineSnapshot(
                `"M24 76 L32 76 Q40 76, 40 68 L40 32 Q40 24, 48 24 L76 24 L68 16 M76 24 L68 32"`
            );
        });

        it("Zigzags if width is lesser than thresold", () => {
            expect(
                removeExtraWhitespaces(getUpRightArrow(threshold - 10, 100))
            ).toMatchInlineSnapshot(
                `"M24 76 L32 76 Q40 76, 40 68 L40 64 Q40 56, 32 56 L16 56 Q8 56, 8 48 L8 32 Q8 24, 16 24 L46 24 L38 16 M46 24 L38 32"`
            );
        });
    });

    describe("getUpLeftArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getUpLeftArrow(100, 100))).toMatchInlineSnapshot(
                `"M76 76 L84 76 Q92 76, 92 68 L92 64 Q92 56, 84 56 L16 56 Q8 56, 8 48 L8 32 Q8 24, 16 24 L24 24 L16 16 M24 24 L16 32"`
            );
        });
    });
});
