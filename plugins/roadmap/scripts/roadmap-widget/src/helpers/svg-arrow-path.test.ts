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
                `"M17 17 L25 17 Q33 17, 33 25 L33 75 Q33 83, 41 83 L83 83 L75 75 M83 83 L75 91"`
            );
        });

        it("Zigzags if width is lesser than thresold", () => {
            expect(
                removeExtraWhitespaces(getDownRightArrow(threshold - 10, 100))
            ).toMatchInlineSnapshot(
                `"M17 17 L25 17 Q33 17, 33 25 L33 29 Q33 37, 25 37 L9 37 Q1 37, 1 45 L1 75 Q1 83, 9 83 L39 83 L31 75 M39 83 L31 91"`
            );
        });
    });

    describe("getDownLeftArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getDownLeftArrow(100, 100))).toMatchInlineSnapshot(
                `"M83 17 L91 17 Q99 17, 99 25 L99 29 Q99 37, 91 37 L9 37 Q1 37, 1 45 L1 75 Q1 83, 9 83 L17 83 L9 75 M17 83 L9 91"`
            );
        });
    });

    describe("getUpRightArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getUpRightArrow(100, 100))).toMatchInlineSnapshot(
                `"M17 83 L25 83 Q33 83, 33 75 L33 25 Q33 17, 41 17 L83 17 L75 9 M83 17 L75 25"`
            );
        });

        it("Zigzags if width is lesser than thresold", () => {
            expect(
                removeExtraWhitespaces(getUpRightArrow(threshold - 10, 100))
            ).toMatchInlineSnapshot(
                `"M17 83 L25 83 Q33 83, 33 75 L33 71 Q33 63, 25 63 L9 63 Q1 63, 1 55 L1 25 Q1 17, 9 17 L39 17 L31 9 M39 17 L31 25"`
            );
        });
    });

    describe("getUpLeftArrow", () => {
        it("Computes a down right path", () => {
            expect(removeExtraWhitespaces(getUpLeftArrow(100, 100))).toMatchInlineSnapshot(
                `"M83 83 L91 83 Q99 83, 99 75 L99 71 Q99 63, 91 63 L9 63 Q1 63, 1 55 L1 25 Q1 17, 9 17 L17 17 L9 9 M17 17 L9 25"`
            );
        });
    });
});
