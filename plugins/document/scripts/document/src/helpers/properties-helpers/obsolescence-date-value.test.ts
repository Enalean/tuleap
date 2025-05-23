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

import { describe, expect, it } from "vitest";
import {
    getObsolescenceDateValueInput,
    formatObsolescenceDateValue,
} from "./obsolescence-date-value";
import moment from "moment/moment";

describe("getObsolescenceDateValueInput", () => {
    it(`Given a "permanent" date value
    Then the returned date should be empty`, () => {
        const date_result = getObsolescenceDateValueInput("permanent");
        expect("").toStrictEqual(date_result);
    });
    it(`Given a "today" date value
    Then the returned date should be the current date`, () => {
        const expected_date = moment().format("YYYY-MM-DD");
        const date_result = getObsolescenceDateValueInput("today");
        expect(expected_date).toStrictEqual(date_result);
    });
    it(`Given a number of month after the current date
    Then the returned date should be the current + 6 months`, () => {
        const expected_date = moment().add(6, "M").format("YYYY-MM-DD");
        const date_result = getObsolescenceDateValueInput("6");
        expect(expected_date).toStrictEqual(date_result);
    });
});

describe("formatObsolescenceDateValue", () => {
    it(`Given an empty date value
    Then the returned date should be empty`, () => {
        const date_result = formatObsolescenceDateValue("");
        expect(date_result).toBe("");
    });
    it(`Given a string datetime value
    Then the returned date should be the date formatted to YYYY-MM-DD`, () => {
        const date_result = formatObsolescenceDateValue("2022-12-09T10:30:28+01:00");
        expect(date_result).toBe("2022-12-09");
    });
    it(`Given a string date value
    Then the returned date should be the date formatted to YYYY-MM-DD`, () => {
        const date_result = formatObsolescenceDateValue("2022-12-09");
        expect(date_result).toBe("2022-12-09");
    });
});
