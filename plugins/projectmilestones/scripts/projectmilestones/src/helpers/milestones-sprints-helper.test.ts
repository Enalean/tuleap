/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { MilestoneData } from "../type";
import {
    getSortedSprints,
    openSprintsExist,
    closedSprintsExists,
} from "./milestones-sprints-helper";

describe("Milestones Sprints Helper", () => {
    describe("openSprintsExists", () => {
        it("When total_sprints is undefined, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is null, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: null,
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is defined but open_sprints is undefined, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: 10,
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is defined but open_sprints is null, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: 10,
                open_sprints: null,
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is defined but open_sprints is an empty array, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: 10,
                open_sprints: [] as MilestoneData[],
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is defined but there are open_sprints, Then true returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: 10,
                open_sprints: [{ id: 1 } as MilestoneData],
            } as MilestoneData;

            const exists = openSprintsExist(release_data);
            expect(exists).toBe(true);
        });
    });

    describe("closedSprintsExist", () => {
        it("When total_sprints is undefined, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
            } as MilestoneData;

            const exists = closedSprintsExists(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is null, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: null,
            } as MilestoneData;

            const exists = closedSprintsExists(release_data);
            expect(exists).toBe(false);
        });
        it("When total_sprints is defined but total_closed_sprints is undefined, Then false returned", () => {
            const release_data: MilestoneData = {
                id: 10,
                total_sprint: 10,
            } as MilestoneData;

            const exists = closedSprintsExists(release_data);
            expect(exists).toBe(false);
        });
        it("When closed_sprints is 0, Then false returned", () => {
            const release: MilestoneData = {
                total_sprint: 10,
                total_closed_sprint: 0,
            } as MilestoneData;

            const exist = closedSprintsExists(release);
            expect(exist).toBe(false);
        });

        it("When there are some closed sprints, Then true returned", () => {
            const release: MilestoneData = {
                total_sprint: 10,
                total_closed_sprint: 2,
            } as MilestoneData;

            const exist = closedSprintsExists(release);
            expect(exist).toBe(true);
        });
    });

    describe("getSortedSprints", () => {
        it("When there are no sprints, Then there is 2 empty arrays", () => {
            const { closed_sprints, open_sprints } = getSortedSprints([]);
            expect(closed_sprints).toStrictEqual([]);
            expect(open_sprints).toStrictEqual([]);
        });

        it("When there are only closed sprints, Then open_sprints array is empty", () => {
            const sprints: MilestoneData[] = [
                {
                    id: 10,
                    semantic_status: "closed",
                } as MilestoneData,
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
            ];

            const { closed_sprints, open_sprints } = getSortedSprints(sprints);
            expect(closed_sprints).toStrictEqual(sprints);
            expect(open_sprints).toStrictEqual([]);
        });

        it("When there are open and closed sprints, Then sprints are sorted", () => {
            const sprints: MilestoneData[] = [
                {
                    id: 10,
                    semantic_status: "open",
                } as MilestoneData,
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
            ];

            const { closed_sprints, open_sprints } = getSortedSprints(sprints);
            expect(closed_sprints).toStrictEqual([
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
                {
                    id: 11,
                    semantic_status: "closed",
                } as MilestoneData,
            ]);
            expect(open_sprints).toStrictEqual([
                {
                    id: 10,
                    semantic_status: "open",
                } as MilestoneData,
            ]);
        });
    });
});
