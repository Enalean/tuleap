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

const offset = 8;
const radius = offset;
export const gap = offset + radius + 1;
export const threshold = 2 * offset + 2 * radius + 2 * gap;
const arrow = 5;

const TOP = "top";
const RIGHT = "right";
const BOTTOM = "bottom";
const LEFT = "left";

type Orientation = "top" | "right" | "bottom" | "left";

export class Path {
    private path: string;
    private x: number;
    private y: number;
    private readonly width: number;
    private readonly height: number;
    private orientation: Orientation;

    constructor(start_x: number, start_y: number, width: number, height: number) {
        this.x = start_x;
        this.y = start_y;
        this.width = width;
        this.height = height;
        this.orientation = RIGHT;

        this.path = `M${start_x} ${start_y}`;
        this.x += offset;
        this.path += ` L${this.x} ${this.y}`;
    }

    /**
     *  We need to stop before the gap to :
     *  - be able to turn to the left or the right
     *  - or put an arrow
     *
     *   ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
     *   ┃                            ┃
     *   ┃                            ┃
     *   ┃    ┌──────────────────┐    ┃
     *   ┃    │                  │    ┃
     *   ┃    |  ━━━━━·········→ |    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    └──────────────────┘----┃
     *   ┃                       |    ┃
     *   ┃                       |    ┃
     *   ┗━━━━━━━━━━━━━━━━━━━━━━━|━━━━┛-
     *                           |    |
     *                     gap   |    |
     *                     ----->+----+
     *                           |    |
     */
    public forwardAndStopBeforeGap(): Path {
        if (this.orientation === RIGHT) {
            this.x = this.width - gap - radius;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        if (this.orientation === LEFT) {
            this.x = gap + radius;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        if (this.orientation === TOP) {
            this.y = gap + radius;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        // BOTTOM
        this.y = this.height - gap - radius;
        this.path += ` L${this.x} ${this.y}`;

        return this;
    }

    /**
     *  We need to stop into the gap to be able to do a half turn (before putting an arrow for example)
     *
     *   ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
     *   ┃                            ┃
     *   ┃                            ┃
     *   ┃    ┌──────────────────┐    ┃
     *   ┃    │                  │    ┃
     *   ┃    |  ━━━━━············→   ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    └──────────────────┘----┃
     *   ┃                       |    ┃
     *   ┃                       |    ┃
     *   ┗━━━━━━━━━━━━━━━━━━━━━━━|━━━━┛-
     *                           |    |
     *                     gap   |    |
     *                     ----->+----+
     *                           |    |
     */
    public forwardAndStopIntoGap(): Path {
        if (this.orientation === RIGHT) {
            this.x = this.width - gap + offset;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        if (this.orientation === LEFT) {
            this.x = gap - offset;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        if (this.orientation === TOP) {
            this.y = gap - offset;
            this.path += ` L${this.x} ${this.y}`;

            return this;
        }

        this.y = this.height - gap + offset;
        this.path += ` L${this.x} ${this.y}`;

        return this;
    }

    public turnLeft(): Path {
        if (this.orientation === RIGHT) {
            this.x += radius;
            this.y -= radius;
            this.path += ` Q${this.x} ${this.y + radius}, ${this.x} ${this.y}`;
            this.orientation = TOP;

            return this;
        }

        if (this.orientation === LEFT) {
            this.x -= radius;
            this.y += radius;
            this.path += ` Q${this.x} ${this.y - radius}, ${this.x} ${this.y}`;
            this.orientation = BOTTOM;

            return this;
        }

        if (this.orientation === TOP) {
            this.x -= radius;
            this.y -= radius;
            this.path += ` Q${this.x + radius} ${this.y}, ${this.x} ${this.y}`;
            this.orientation = LEFT;

            return this;
        }

        // Bottom
        this.x += radius;
        this.y += radius;
        this.path += ` Q${this.x - radius} ${this.y}, ${this.x} ${this.y}`;
        this.orientation = RIGHT;

        return this;
    }

    public turnRight(): Path {
        if (this.orientation === RIGHT) {
            this.x += radius;
            this.y += radius;
            this.path += ` Q${this.x} ${this.y - radius}, ${this.x} ${this.y}`;
            this.orientation = BOTTOM;

            return this;
        }

        if (this.orientation === LEFT) {
            this.x -= radius;
            this.y -= radius;
            this.path += ` Q${this.x} ${this.y + radius}, ${this.x} ${this.y}`;
            this.orientation = TOP;

            return this;
        }

        if (this.orientation === TOP) {
            this.x += radius;
            this.y -= radius;
            this.path += ` Q${this.x - radius} ${this.y}, ${this.x} ${this.y}`;
            this.orientation = RIGHT;

            return this;
        }

        // BOTTOM
        this.x -= radius;
        this.y += radius;
        this.path += ` Q${this.x + radius} ${this.y}, ${this.x} ${this.y}`;
        this.orientation = LEFT;

        return this;
    }

    public halfTurnLeft(height: number): Path {
        this.turnLeft();
        this.halfTurnOffset(height);
        this.turnLeft();

        return this;
    }

    public halfTurnRight(height: number): Path {
        this.turnRight();
        this.halfTurnOffset(height);
        this.turnRight();

        return this;
    }

    private halfTurnOffset(height: number): void {
        const half_turn_offset = height / 2;

        if (this.orientation === RIGHT) {
            this.x += Math.max(0, half_turn_offset - 2 * radius);
        } else if (this.orientation === LEFT) {
            this.x -= Math.max(0, half_turn_offset - 2 * radius);
        } else if (this.orientation === TOP) {
            this.y -= Math.max(0, half_turn_offset - 2 * radius);
        } else {
            // BOTTOM
            this.y += Math.max(0, half_turn_offset - 2 * radius);
        }

        this.path += ` L${this.x} ${this.y}`;
    }

    /**
     *  This arrow is put at the left of the figure
     *
     *   ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
     *   ┃                            ┃
     *   ┃                            ┃
     *   ┃    ┌──────────────────┐    ┃
     *   ┃    │                  │    ┃
     *   ┃  ━▶|                  |    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    └──────────────────┘----┃
     *   ┃                       |    ┃
     *   ┃                       |    ┃
     *   ┗━━━━━━━━━━━━━━━━━━━━━━━|━━━━┛-
     *                           |    |
     *                     gap   |    |
     *                     ----->+----+
     *                           |    |
     */
    public arrowOnTheLeftGap(): string {
        this.x = gap;

        this.path += `
            L${this.x} ${this.y}
            L${this.x - arrow} ${this.y - arrow}
            M${this.x} ${this.y}
            L${this.x - arrow} ${this.y + arrow}`;

        return this.path;
    }

    /**
     *  This arrow is put at the right of the figure
     *
     *   ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
     *   ┃                            ┃
     *   ┃                            ┃
     *   ┃    ┌──────────────────┐    ┃
     *   ┃    │                  │    ┃
     *   ┃    |                ━▶|    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    │                  │    ┃
     *   ┃    └──────────────────┘----┃
     *   ┃                       |    ┃
     *   ┃                       |    ┃
     *   ┗━━━━━━━━━━━━━━━━━━━━━━━|━━━━┛-
     *                           |    |
     *                     gap   |    |
     *                     ----->+----+
     *                           |    |
     */
    public arrowOnTheRightGap(): string {
        this.x = this.width - gap;

        this.path += `
            L${this.x} ${this.y}
            L${this.x - arrow} ${this.y - arrow}
            M${this.x} ${this.y}
            L${this.x - arrow} ${this.y + arrow}`;

        return this.path;
    }

    public toString(): string {
        return this.path;
    }
}

export function startAt(x: number, y: number, width: number, height: number): Path {
    return new Path(x, y, width, height);
}
