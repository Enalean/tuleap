/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { getObsolescenceDateValueInput } from "./obsolescence-date-value.js";
import moment from "moment/moment";

describe("getObsolescenceDateValueInput", () => {
    it(`Given a "permanent" date value
    Then the returned date should be null`, () => {
        const date_result = getObsolescenceDateValueInput("permanent");
        expect(null).toEqual(date_result);
    });
    it(`Given a "today" date value
    Then the returned date should be the current date`, () => {
        const expected_date = moment().format("YYYY-MM-DD");
        const date_result = getObsolescenceDateValueInput("today");
        expect(expected_date).toEqual(date_result);
    });
    it(`Given a number of month after the current date
    Then the returned date should be the current + 6 months`, () => {
        const expected_date = moment().add(6, "M").format("YYYY-MM-DD");
        const date_result = getObsolescenceDateValueInput("6");
        expect(expected_date).toEqual(date_result);
    });
});
