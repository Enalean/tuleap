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

import { startAt } from "./path";
import type { Path } from "./path";
import removeExtraWhitespaces from "./remove-extra-whitespaces";

describe("Path", () => {
    it("Starts at the given position and automatically move forward to the right", () => {
        expect(removeExtraWhitespaces(startAt(50, 50, 100, 100).toString())).toMatchInlineSnapshot(
            `"M50 50 L58 50"`
        );
    });

    it("Displays an arrow on the left", () => {
        expect(
            removeExtraWhitespaces(startAt(0, 50, 100, 100).arrowOnTheLeftGap())
        ).toMatchInlineSnapshot(`"M0 50 L8 50 L24 50 L16 42 M24 50 L16 58"`);
    });

    it("Displays an arrow on the right", () => {
        expect(
            removeExtraWhitespaces(startAt(0, 50, 100, 100).arrowOnTheRightGap())
        ).toMatchInlineSnapshot(`"M0 50 L8 50 L76 50 L68 42 M76 50 L68 58"`);
    });

    describe("Going top", () => {
        let path: Path;

        beforeEach(() => {
            path = startAt(50, 50, 100, 100).turnLeft();
        });

        it("Turns to the left", () => {
            expect(removeExtraWhitespaces(path.turnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 58 34"`
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 74 34"`
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 42 L66 32"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 42 L66 16"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 58 34 L54 34 Q46 34, 46 42"`
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 74 34 L78 34 Q86 34, 86 42"`
            );
        });
    });

    describe("Going right", () => {
        let path: Path;

        beforeEach(() => {
            path = startAt(50, 50, 100, 100);
        });

        it("Turns to the left", () => {
            expect(removeExtraWhitespaces(path.turnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42"`
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58"`
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 L68 50"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 L84 50"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 L66 38 Q66 30, 58 30"`
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 L66 62 Q66 70, 58 70"`
            );
        });
    });

    describe("Going bottom", () => {
        let path: Path;

        beforeEach(() => {
            path = startAt(50, 50, 100, 100).turnRight();
        });

        it("Turns to the left", () => {
            expect(removeExtraWhitespaces(path.turnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 74 66"`
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66"`
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 L66 68"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 L66 84"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 74 66 L78 66 Q86 66, 86 58"`
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L54 66 Q46 66, 46 58"`
            );
        });
    });

    describe("Going left", () => {
        let path: Path;

        beforeEach(() => {
            path = startAt(50, 50, 100, 100).turnRight().turnRight();
        });

        it("Turns to the left", () => {
            expect(removeExtraWhitespaces(path.turnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 74"`
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 58"`
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L32 66"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString())
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L16 66"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 74 L50 78 Q50 86, 58 86"`
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 58 L50 54 Q50 46, 58 46"`
            );
        });
    });
});
