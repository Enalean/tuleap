/**
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

import { getAdditionalMonths } from "./additional-months";

describe("additional-months", () => {
    it.each([[-1], [0]])(
        "Returns empty array if nb missing months is lesser than 0",
        (nb_missing_months) => {
            expect(getAdditionalMonths(new Date(2020, 3, 15), nb_missing_months)).toStrictEqual([]);
        }
    );

    it("Returns an array of additional months", () => {
        expect(
            getAdditionalMonths(new Date(2020, 3, 15), 3).map((month) => month.toDateString())
        ).toStrictEqual(["Fri May 01 2020", "Mon Jun 01 2020", "Wed Jul 01 2020"]);
    });
});
