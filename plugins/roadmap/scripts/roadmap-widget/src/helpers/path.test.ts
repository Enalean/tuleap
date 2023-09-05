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

import { gap, startAt } from "./path";
import type { Path } from "./path";
import removeExtraWhitespaces from "./remove-extra-whitespaces";
import { Styles } from "./styles";

describe("Path", () => {
    it("should have a not too big gap to not produce scroll overflow when the arrow points to the last task", () => {
        expect(gap).toBeLessThanOrEqual(Styles.TASK_HEIGHT_IN_PX / 2);
    });

    it("Starts at the given position and automatically move forward to the right", () => {
        expect(removeExtraWhitespaces(startAt(50, 50, 100, 100).toString())).toMatchInlineSnapshot(
            `"M50 50 L58 50"`,
        );
    });

    it("Displays an arrow on the left", () => {
        expect(
            removeExtraWhitespaces(startAt(0, 50, 100, 100).arrowOnTheLeftGap()),
        ).toMatchInlineSnapshot(`"M0 50 L8 50 L17 50 L12 45 M17 50 L12 55"`);
    });

    it("Displays an arrow on the right", () => {
        expect(
            removeExtraWhitespaces(startAt(0, 50, 100, 100).arrowOnTheRightGap()),
        ).toMatchInlineSnapshot(`"M0 50 L8 50 L83 50 L78 45 M83 50 L78 55"`);
    });

    describe("Going top", () => {
        let path: Path;

        beforeEach(() => {
            path = startAt(50, 50, 100, 100).turnLeft();
        });

        it("Turns to the left", () => {
            expect(removeExtraWhitespaces(path.turnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 58 34"`,
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 74 34"`,
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 42 L66 25"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 42 L66 9"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 58 34 L53 34 Q45 34, 45 42"`,
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 Q66 34, 74 34 L79 34 Q87 34, 87 42"`,
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
                `"M50 50 L58 50 Q66 50, 66 42"`,
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58"`,
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 L75 50"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 L91 50"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 42 L66 37 Q66 29, 58 29"`,
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 L66 63 Q66 71, 58 71"`,
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
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 74 66"`,
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66"`,
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 L66 75"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 L66 91"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 74 66 L79 66 Q87 66, 87 58"`,
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L53 66 Q45 66, 45 58"`,
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
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 74"`,
            );
        });

        it("Turns to the right", () => {
            expect(removeExtraWhitespaces(path.turnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 58"`,
            );
        });

        it("Forwards but stop before the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopBeforeGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L25 66"`);
        });

        it("Forwards but stop inside the gap", () => {
            expect(
                removeExtraWhitespaces(path.forwardAndStopIntoGap().toString()),
            ).toMatchInlineSnapshot(`"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 L9 66"`);
        });

        it("Half turns to the left", () => {
            expect(removeExtraWhitespaces(path.halfTurnLeft().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 74 L50 79 Q50 87, 58 87"`,
            );
        });

        it("Half turns to the right", () => {
            expect(removeExtraWhitespaces(path.halfTurnRight().toString())).toMatchInlineSnapshot(
                `"M50 50 L58 50 Q66 50, 66 58 Q66 66, 58 66 Q50 66, 50 58 L50 53 Q50 45, 58 45"`,
            );
        });
    });
});
