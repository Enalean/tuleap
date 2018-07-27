/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export { setFromTracker, getTrackerName, getTrackerColor, getArtifactId };

let tracker_name, tracker_color, artifact_id;

function setFromTracker(name_tracker, color_tracker, id_artifact) {
    tracker_name = name_tracker;
    tracker_color = color_tracker;
    artifact_id = id_artifact;
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
