/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

export interface State {
    error_message: string | null;
    success_message: string | null;
    invalid_trackers: Array<Tracker>;
    reading_mode: boolean;
    is_report_saved: boolean;
}

export interface Tracker {
    id: number;
    label: string;
}

export interface Project {
    label: string;
}

export interface Report {
    trackers: Array<Tracker>;
    expert_query: string;
    invalid_trackers: Array<Tracker>;
}

export interface ReadingReport {
    trackers: Map<number, Tracker>;
    expert_query: string;
    invalid_trackers: Array<Tracker>;
}

export interface ArtifactsCollection {
    artifacts: Artifact[];
    total: string;
}

export interface Artifact {
    id: number;
}
