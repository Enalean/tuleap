/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { delayedQuerier } from "./delayed-querier";

describe("DelayedQuerier", () => {
    beforeEach(() => {
        jest.useFakeTimers();
    });

    it("should schedule the query", () => {
        const query = jest.fn();
        const querier = delayedQuerier();

        querier.scheduleQuery(() => query());

        expect(query).not.toHaveBeenCalled();
        jest.advanceTimersByTime(1000);
        expect(query).toHaveBeenCalled();
    });

    it("should only schedule one query at a time", () => {
        const query_1 = jest.fn();
        const query_2 = jest.fn();
        const querier = delayedQuerier();

        querier.scheduleQuery(() => query_1());
        querier.scheduleQuery(() => query_2());

        jest.advanceTimersByTime(1000);
        expect(query_1).not.toHaveBeenCalled();
        expect(query_2).toHaveBeenCalled();
    });

    it("should allow to cancel the query", () => {
        const query = jest.fn();
        const querier = delayedQuerier();

        querier.scheduleQuery(() => query());
        querier.cancelPendingQuery();

        jest.advanceTimersByTime(1000);
        expect(query).not.toHaveBeenCalled();
    });
});
