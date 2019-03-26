/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

describe("DateUtils:", () => {
    const now = moment("2019/02/23 09:37:20 +0001", "YYYY/MM/DD HH:mm:ss Z").toDate();

    beforeEach(() => {
        //spyOn(window, "Date").andCallFake(() => now);
        //var today = moment('2015-10-19').toDate();
        jasmine.clock().mockDate(now);

        DateFormatter.setOptions({
            user_locale: "fr_FR",
            user_timezone: "Europe/Paris",
            format: "d/m/Y H:i"
        });
    });

    afterEach(jasmine.clock().uninstall);

    describe("#format", () => {
        it("format date", () => {
            expect(DateFormatter.format("2019-03-22T10:01:48+00:00")).toEqual("22/03/2019 11:01");
        });
    });

    describe("#getFromNow", () => {
        it("formats date and returns interval from now", () => {
            expect(DateFormatter.getFromNow("2016-01-01T23:35:01")).toEqual("il y a 3 ans");
        });
    });
});
