/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import DateFormatter from "./date-utils";
import moment from "moment";
import "moment/locale/de";

describe("DateUtils:", () => {
    const now = moment("2019/02/23 09:37:20 +0001", "YYYY/MM/DD HH:mm:ss Z").toDate();

    beforeEach(() => {
        jest.spyOn(Date, "now").mockReturnValue(now.getTime());

        DateFormatter.setOptions({
            user_locale: "de",
            user_timezone: "America/Chicago",
            format: "d/m/Y H:i",
        });
    });

    describe("#format", () => {
        it("format date", () => {
            expect(DateFormatter.format("2019-03-22T10:01:48+00:00")).toBe("22/03/2019 05:01");
        });
    });

    describe("#humanFormat", () => {
        it("formats date to make it readable", () => {
            expect(DateFormatter.humanFormat("2019-03-22T10:01:48+00:00")).toBe(
                "22. März 2019 05:01",
            );
        });
    });

    describe("#getFromNow", () => {
        it("formats date and returns interval from now", () => {
            expect(DateFormatter.getFromNow("2016-01-01T23:35:01")).toBe("vor 3 Jahren");
        });
    });

    describe("#formatToISO", () => {
        it("formats date to ISO", () => {
            expect(DateFormatter.formatToISO("2019-03-22 10:01")).toBe("2019-03-22T10:01:00-05:00");
        });
    });
});
