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

export {
    setFromTracker,
    getTrackerId,
    getTrackerName,
    getTrackerColor,
    getArtifactId,
    getProjectId,
};

let tracker_id, tracker_name, tracker_color, artifact_id, project_id;

function setFromTracker(id_tracker, name_tracker, color_tracker, id_artifact, id_project) {
    tracker_id = id_tracker;
    tracker_name = name_tracker;
    tracker_color = color_tracker;
    artifact_id = id_artifact;
    project_id = id_project;
}

function getTrackerId() {
    return tracker_id;
}

function getProjectId() {
    return project_id;
}

function getTrackerName() {
    return tracker_name;
}

function getTrackerColor() {
    return tracker_color;
}

function getArtifactId() {
    return artifact_id;
}
