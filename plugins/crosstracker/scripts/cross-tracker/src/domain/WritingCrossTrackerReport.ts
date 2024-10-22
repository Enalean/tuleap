/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import type { Result } from "neverthrow";
import { err, ok } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ProjectInfo, TrackerAndProject, TrackerInfo } from "../type";
import type { ReadingCrossTrackerReport } from "./ReadingCrossTrackerReport";
import { TooManyTrackersSelectedFault } from "./TooManyTrackersSelectedFault";

export class WritingCrossTrackerReport {
    trackers: Map<number, TrackerAndProject>;
    expert_query: string;
    expert_mode: boolean;

    constructor() {
        this.trackers = new Map();
        this.expert_query = "";
        this.expert_mode = false;
    }

    addTracker(project: ProjectInfo, tracker: TrackerInfo): Result<null, Fault> {
        if (this.trackers.size === 25) {
            return err(TooManyTrackersSelectedFault());
        }

        const tracker_and_project: TrackerAndProject = { project, tracker };
        this.trackers.set(tracker.id, tracker_and_project);
        return ok(null);
    }

    removeTracker(tracker_id: number): void {
        this.trackers.delete(tracker_id);
    }

    duplicateFromReport(report: ReadingCrossTrackerReport): void {
        this.trackers = new Map(report.trackers);
        this.expert_query = report.expert_query;
        this.expert_mode = report.expert_mode;
    }

    getTrackerIds(): Array<number> {
        return Array.from(this.trackers.keys());
    }

    getTrackers(): ReadonlyArray<TrackerAndProject> {
        return Array.from(this.trackers.values());
    }

    setExpertQuery(expert_query: string): void {
        this.expert_query = expert_query;
    }

    toggleExpertMode(): void {
        this.expert_mode = !this.expert_mode;
    }
}
