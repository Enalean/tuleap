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

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type { TrackerAndProject, TrackerInfo } from "./type";
import type ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";

export interface TrackerForInit extends TrackerInfo {
    uri: string;
    project: ProjectReference;
}

export default class BackendCrossTrackerReport {
    expert_query: string;
    loaded: boolean;
    trackers: Map<number, TrackerAndProject>;
    constructor() {
        this.loaded = false;
        this.trackers = new Map();
        this.expert_query = "";
    }

    init(trackers: Map<number, TrackerForInit>, expert_query: string): void {
        if (trackers) {
            this.clearTrackers();
            for (const tracker_for_init of trackers.values()) {
                const tracker = { id: tracker_for_init.id, label: tracker_for_init.label };
                const light_project = {
                    id: tracker_for_init.project.id,
                    label: tracker_for_init.project.label,
                    uri: tracker_for_init.project.uri,
                };
                this.trackers.set(tracker_for_init.id, {
                    project: light_project,
                    tracker: tracker,
                });
            }
        }

        this.expert_query = expert_query;
    }

    clearTrackers(): void {
        this.trackers.clear();
    }

    duplicateFromReport(report: ReadingCrossTrackerReport): void {
        this.trackers = new Map(report.trackers);
        this.expert_query = report.expert_query;
    }

    getTrackerIds(): Array<number> {
        return [...this.trackers.keys()];
    }

    getExpertQuery(): string {
        return this.expert_query;
    }
}
