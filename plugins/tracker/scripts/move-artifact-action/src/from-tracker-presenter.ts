/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { ColorName } from "@tuleap/core-constants";

let tracker_id: number,
    tracker_name: string,
    tracker_color: ColorName,
    artifact_id: number,
    project_id: number;

export function setFromTracker(
    id_tracker: number,
    name_tracker: string,
    color_tracker: ColorName,
    id_artifact: number,
    id_project: number
): void {
    tracker_id = id_tracker;
    tracker_name = name_tracker;
    tracker_color = color_tracker;
    artifact_id = id_artifact;
    project_id = id_project;
}

export function getTrackerId(): number {
    return tracker_id;
}

export function getProjectId(): number {
    return project_id;
}

export function getTrackerName(): string {
    return tracker_name;
}

export function getTrackerColor(): ColorName {
    return tracker_color;
}

export function getArtifactId(): number {
    return artifact_id;
}
