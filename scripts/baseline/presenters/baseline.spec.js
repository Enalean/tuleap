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

import { restore, rewire$getUser } from "../api/rest-querier";
import { presentBaseline } from "./baseline";
import { create } from "../support/factories";

describe("baseline presenter:", () => {
    afterEach(restore);

    describe("presentBaseline()", () => {
        let getUserResolve;
        let getUserReject;
        let presentation;

        beforeEach(() => {
            const getUser = jasmine.createSpy("getUser");
            getUser.and.returnValue(
                new Promise((resolve, reject) => {
                    getUserResolve = resolve;
                    getUserReject = reject;
                })
            );
            rewire$getUser(getUser);

            const baseline = create("baseline", { author_id: 1 });
            presentation = presentBaseline(baseline);
        });

        describe("when getUser() is successful", () => {
            const user = create("user", { id: 1, username: "John Doe" });

            let presented_baseline;

            beforeEach(async () => {
                getUserResolve(user);
                presented_baseline = await presentation;
            });

            it("returns baselines with author", () => {
                expect(presented_baseline.author).toEqual(user);
            });
        });

        describe("when getUser() fail", () => {
            beforeEach(() => {
                getUserReject("Exception reason");
            });

            it("throws exception", async () => {
                try {
                    await presentation;
                    fail("No exception thrown");
                } catch (exception) {
                    expect(exception).toEqual("Exception reason");
                }
            });
        });
    });
});
