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

import { getTrackerId } from "../from-tracker-presenter";
import type { RootState, Project, Tracker } from "./types";

export const sorted_projects = (state: RootState): Project[] =>
    state.projects.sort((a: Project, b: Project) => a.label.localeCompare(b.label));

export const tracker_list_with_disabled_from = (state: RootState): Tracker[] =>
    state.trackers.map((tracker: Tracker) => {
        tracker.disabled = tracker.id === getTrackerId();
        return tracker;
    });

export const not_migrated_fields_count = (state: RootState): number =>
    state.dry_run_fields.fields_not_migrated.length;

export const partially_migrated_fields_count = (state: RootState): number =>
    state.dry_run_fields.fields_partially_migrated.length;

export const fully_migrated_fields_count = (state: RootState): number =>
    state.dry_run_fields.fields_migrated.length;
