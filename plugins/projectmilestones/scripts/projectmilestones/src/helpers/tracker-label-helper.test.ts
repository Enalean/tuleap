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
import { MilestoneData } from "../type";

describe("Tracker Label Helper", () => {
    describe("getTrackerSubmilestoneLabel", () => {
        it("When there is no resources, Then empty string returns", () => {
            const release: MilestoneData = {
                id: 100,
                number_of_artifact_by_trackers: []
            };

            const label = getTrackerSubmilestoneLabel(release);

            expect(label).toEqual("");
        });

        it("When there is no tracker, Then empty string returns", () => {
            const release: MilestoneData = {
                id: 100,
                number_of_artifact_by_trackers: [],
                resources: {
                    content: {
                        accept: {
                            trackers: []
                        }
                    },
                    additional_panes: [],
                    burndown: null,
                    milestones: {
                        accept: {
                            trackers: []
                        }
                    }
                }
            };

            const label = getTrackerSubmilestoneLabel(release);

            expect(label).toEqual("");
        });

        it("When there is a tracker, Then Submilestone label is returned", () => {
            const label_tracker = "sprint";
            const release: MilestoneData = {
                id: 100,
                number_of_artifact_by_trackers: [],
                resources: {
                    content: {
                        accept: {
                            trackers: []
                        }
                    },
                    additional_panes: [],
                    burndown: null,
                    milestones: {
                        accept: {
                            trackers: [
                                {
                                    label: label_tracker
                                }
                            ]
                        }
                    }
                }
            };

            const label = getTrackerSubmilestoneLabel(release);

            expect(label).toEqual(label_tracker);
        });
    });
});
