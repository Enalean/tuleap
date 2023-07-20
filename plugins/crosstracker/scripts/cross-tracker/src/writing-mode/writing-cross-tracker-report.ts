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

import ExtendableError from "extendable-error";
import type { ProjectInfo, TrackerInfo, TrackerAndProject } from "../type";
import type ReadingCrossTrackerReport from "../reading-mode/reading-cross-tracker-report";

export default class WritingCrossTrackerReport {
    trackers: Map<number, TrackerAndProject>;
    expert_query: string;

    constructor() {
        this.trackers = new Map();
        this.expert_query = "";
    }

    addTracker(project: ProjectInfo, tracker: TrackerInfo): void {
        if (this.trackers.size === 25) {
            throw new TooManyTrackersSelectedError();
        }
        if (this.trackers.has(tracker.id)) {
            throw new CannotAddTheSameTrackerTwiceError();
        }

        const tracker_and_project: TrackerAndProject = { project, tracker };
        this.trackers.set(tracker.id, tracker_and_project);
    }

    removeTracker(tracker_id: number): void {
        this.trackers.delete(tracker_id);
    }

    duplicateFromReport(report: ReadingCrossTrackerReport): void {
        this.trackers = new Map(report.trackers);
        this.expert_query = report.expert_query;
    }

    getTrackerIds(): Array<number> {
        return [...this.trackers.keys()];
    }

    getTrackers(): IterableIterator<TrackerAndProject> {
        return this.trackers.values();
    }
}

export class TooManyTrackersSelectedError extends ExtendableError {}
export class CannotAddTheSameTrackerTwiceError extends ExtendableError {}
