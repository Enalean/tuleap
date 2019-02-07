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
 */

import { rewire$get } from "tlp-fetch";
import { mockFetchSuccess } from "tlp-mocks";
import { getBaseline } from "./rest-querier";

describe("Rest queries:", () => {
    describe("for GET actions:", () => {
        let get;
        const return_json = {
            artifact_title: "I want to",
            last_modification_date_before_baseline_date: 1234
        };
        let result;

        beforeEach(() => {
            get = jasmine.createSpy("get");
            mockFetchSuccess(get, { return_json });
            rewire$get(get);
        });

        describe("getBaseline()", () => {
            beforeEach(async () => {
                result = await getBaseline(1, "1995-09-02");
            });

            it("calls baseline API", () =>
                expect(get).toHaveBeenCalledWith("/api/baselines?artifact_id=1&date=1995-09-02"));
            it("returns the baseline", () => expect(result).toEqual(return_json));
        });
    });
});
