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

import { getTrackerSubmilestoneLabel } from "./tracker-label-helper";
import {
    MilestoneData,
    MilestoneResourcesData,
    TrackerProjectLabel,
    TrackerProjectWithoutColor,
} from "../type";

describe("Tracker Label Helper", () => {
    describe("getTrackerSubmilestoneLabel", () => {
        it("When there is no tracker, Then empty string returns", () => {
            const release: MilestoneData = {
                id: 100,
                resources: {
                    content: {
                        accept: {
                            trackers: [] as TrackerProjectWithoutColor[],
                        },
                    },
                    milestones: {
                        accept: {
                            trackers: [] as TrackerProjectLabel[],
                        },
                    },
                } as MilestoneResourcesData,
            } as MilestoneData;

            const label = getTrackerSubmilestoneLabel(release);

            expect(label).toEqual("");
        });

        it("When there is a tracker, Then Submilestone label is returned", () => {
            const label_tracker = "sprint";
            const release: MilestoneData = {
                id: 100,
                resources: {
                    content: {
                        accept: {
                            trackers: [] as TrackerProjectWithoutColor[],
                        },
                    },
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: label_tracker,
                                },
                            ] as TrackerProjectLabel[],
                        },
                    },
                },
            } as MilestoneData;

            const label = getTrackerSubmilestoneLabel(release);
            expect(label).toEqual(label_tracker);
        });
    });
});
