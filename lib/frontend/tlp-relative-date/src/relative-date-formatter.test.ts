/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import formatRelativeDate from "./relative-date-formatter";

const a_second_in_ms = 1000;
const a_minute_in_ms = 60 * a_second_in_ms;
const a_hour_in_ms = 60 * a_minute_in_ms;
const a_day_in_ms = 24 * a_hour_in_ms;
const a_month_in_ms = 30 * a_day_in_ms;
const a_year_in_ms = 12 * a_month_in_ms;

const now = 1234567890000;

describe("relative-date-formatter", () => {
    it("Displays X seconds ago", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now))).toBe("0 seconds ago");
        expect(formatRelativeDate("en-US", new Date(now - a_second_in_ms), new Date(now))).toBe(
            "1 second ago",
        );
        expect(
            formatRelativeDate("en-US", new Date(now - 59 * a_second_in_ms), new Date(now)),
        ).toBe("59 seconds ago");
    });
    it("Displays X minutes ago", () => {
        expect(formatRelativeDate("en-US", new Date(now - a_minute_in_ms), new Date(now))).toBe(
            "1 minute ago",
        );
        expect(
            formatRelativeDate("en-US", new Date(now - 44 * a_minute_in_ms), new Date(now)),
        ).toBe("44 minutes ago");
    });
    it("Displays X hours ago", () => {
        expect(
            formatRelativeDate("en-US", new Date(now - 45 * a_minute_in_ms), new Date(now)),
        ).toBe("1 hour ago");
        expect(formatRelativeDate("en-US", new Date(now - a_hour_in_ms), new Date(now))).toBe(
            "1 hour ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 2 * a_hour_in_ms), new Date(now))).toBe(
            "2 hours ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 23 * a_hour_in_ms), new Date(now))).toBe(
            "23 hours ago",
        );
    });
    it("Displays X days ago", () => {
        expect(formatRelativeDate("en-US", new Date(now - a_day_in_ms), new Date(now))).toBe(
            "1 day ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 2 * a_day_in_ms), new Date(now))).toBe(
            "2 days ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 29 * a_day_in_ms), new Date(now))).toBe(
            "29 days ago",
        );
    });
    it("Displays X months ago", () => {
        expect(formatRelativeDate("en-US", new Date(now - a_month_in_ms), new Date(now))).toBe(
            "1 month ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 2 * a_month_in_ms), new Date(now))).toBe(
            "2 months ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 11 * a_month_in_ms), new Date(now))).toBe(
            "11 months ago",
        );
    });
    it("Displays X years ago", () => {
        expect(formatRelativeDate("en-US", new Date(now - a_year_in_ms), new Date(now))).toBe(
            "1 year ago",
        );
        expect(formatRelativeDate("en-US", new Date(now - 2 * a_year_in_ms), new Date(now))).toBe(
            "2 years ago",
        );
    });
    it("Displays in X seconds", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_second_in_ms))).toBe(
            "in 1 second",
        );
        expect(
            formatRelativeDate("en-US", new Date(now), new Date(now - 59 * a_second_in_ms)),
        ).toBe("in 59 seconds");
    });
    it("Displays in X minutes", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_minute_in_ms))).toBe(
            "in 1 minute",
        );
        expect(
            formatRelativeDate("en-US", new Date(now), new Date(now - 44 * a_minute_in_ms)),
        ).toBe("in 44 minutes");
    });
    it("Displays in X hours", () => {
        expect(
            formatRelativeDate("en-US", new Date(now), new Date(now - 45 * a_minute_in_ms)),
        ).toBe("in 1 hour");
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_hour_in_ms))).toBe(
            "in 1 hour",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 2 * a_hour_in_ms))).toBe(
            "in 2 hours",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 23 * a_hour_in_ms))).toBe(
            "in 23 hours",
        );
    });
    it("Displays in X days", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_day_in_ms))).toBe(
            "in 1 day",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 2 * a_day_in_ms))).toBe(
            "in 2 days",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 29 * a_day_in_ms))).toBe(
            "in 29 days",
        );
    });
    it("Displays in X months", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_month_in_ms))).toBe(
            "in 1 month",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 2 * a_month_in_ms))).toBe(
            "in 2 months",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 11 * a_month_in_ms))).toBe(
            "in 11 months",
        );
    });
    it("Displays in X years", () => {
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - a_year_in_ms))).toBe(
            "in 1 year",
        );
        expect(formatRelativeDate("en-US", new Date(now), new Date(now - 2 * a_year_in_ms))).toBe(
            "in 2 years",
        );
    });
});
