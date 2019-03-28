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

import router from "./index";

describe("Router:", () => {
    describe("when navigating to BaselinesPage", () => {
        it("updates documents title", () => {
            router.push({ name: "BaselinesPage", params: { project_name: "My project" } });
            expect(window.document.title).toEqual("Baselines - Tuleap");
        });
    });

    describe("when navigating to BaselineContentPage", () => {
        it("updates documents title", () => {
            router.push({
                name: "BaselineContentPage",
                params: { project_name: "My project", baseline_id: 9 }
            });
            expect(window.document.title).toEqual("Baseline #9 - Tuleap");
        });
    });

    describe("when navigating to ComparisonPage", () => {
        it("updates documents title", () => {
            router.push({
                name: "ComparisonPage",
                params: { project_name: "My project", from_baseline_id: 8, to_baseline_id: 9 }
            });
            expect(window.document.title).toEqual("Baselines comparison #8/#9 - Tuleap");
        });
    });
});
