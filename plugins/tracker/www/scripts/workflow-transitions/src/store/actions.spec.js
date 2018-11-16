/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { mockFetchError } from "tlp-mocks";
import { loadTracker } from "./actions.js";
import { rewire$getTracker } from "../api/rest-querier.js";

describe("Store actions:", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: {}
        };
    });

    describe("loadTracker()", () => {
        let getTracker;
        beforeEach(() => {
            getTracker = jasmine.createSpy("getTracker");
            rewire$getTracker(getTracker);
        });

        it("fetches tracker asynchronously and store it as current tracker", async () => {
            const tracker = { id: 12 };
            getTracker.and.returnValue(Promise.resolve(tracker));

            await loadTracker(context);

            expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", tracker);
            expect(context.commit).toHaveBeenCalledWith("stopCurrentTrackerLoading");
        });

        it("stores loading failure when server request fail", async () => {
            mockFetchError(getTracker, {});

            await loadTracker(context);

            expect(context.commit).toHaveBeenCalledWith("failCurrentTrackerLoading");
        });
    });
});
