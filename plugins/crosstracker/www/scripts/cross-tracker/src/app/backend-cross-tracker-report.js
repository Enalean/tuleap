/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

export default class BackendCrossTrackerReport {
    constructor(report_id) {
        this.loaded       = false;
        this.report_id    = report_id;
        this.trackers     = new Map();
        this.expert_query = '';
    }

    initTrackers(trackers) {
        this.clearTrackers();
        for (const { id, label, project } of trackers) {
            const tracker       = { id, label };
            const light_project = { id: project.id, label: project.label };
            this.trackers.set(id, {
                project: light_project,
                tracker
            });
        }
    }

    clearTrackers() {
        this.trackers.clear();
    }

    duplicateFromReport(report) {
        this.trackers = new Map(report.trackers);
        this.expert_query = report.expert_query;
    }

    getTrackerIds() {
        return [...this.trackers.keys()];
    }

    getExpertQuery() {
        return this.expert_query;
    }
}
