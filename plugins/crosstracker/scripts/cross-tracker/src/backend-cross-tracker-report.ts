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

import type { Report, TrackerAndProject, Project, Tracker } from "./type";

export interface TrackerForInit extends Tracker {
    project: Project;
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

    init(trackers: Array<TrackerForInit>, expert_query: string): void {
        if (trackers) {
            this.clearTrackers();
            for (const { id, label, project } of trackers) {
                const tracker = { id, label };
                const light_project = { id: project.id, label: project.label };
                this.trackers.set(id, {
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

    duplicateFromReport(report: Report): void {
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
