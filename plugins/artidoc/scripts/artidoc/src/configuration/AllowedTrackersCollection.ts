/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { Project } from "@/helpers/project.type";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export interface TitleFieldDefinition {
    readonly field_id: number;
    readonly label: string;
    readonly type: "string" | "text";
    readonly default_value: string;
}

interface DescriptionFieldDefinition {
    readonly label: string;
    readonly type: "text";
    readonly default_value: {
        readonly format: "text" | "html" | "commonmark";
        readonly content: string;
    };
}

interface FileFieldDefinition {
    readonly label: string;
    readonly type: "file";
    readonly upload_url: string;
}

interface BaseTracker {
    readonly id: number;
    readonly label: string;
    readonly color: string;
    readonly item_name: string;
    readonly file: null | FileFieldDefinition;
    readonly project: Project;
}

interface TrackerNotSubmittable extends BaseTracker {
    readonly title: null | TitleFieldDefinition;
    readonly description: null | DescriptionFieldDefinition;
}

export interface TrackerWithSubmittableSection extends BaseTracker {
    readonly title: TitleFieldDefinition;
    readonly description: DescriptionFieldDefinition;
}

export type Tracker = TrackerNotSubmittable | TrackerWithSubmittableSection;

export function isTrackerWithSubmittableSection(
    tracker: Tracker,
): tracker is TrackerWithSubmittableSection {
    return tracker.title !== null && tracker.description !== null;
}

export interface AllowedTrackersCollection extends Iterable<Tracker> {
    isEmpty(): boolean;
}

export const ALLOWED_TRACKERS: StrictInjectionKey<AllowedTrackersCollection> =
    Symbol("Allowed trackers");

export function buildAllowedTrackersCollection(
    trackers: ReadonlyArray<Tracker>,
): AllowedTrackersCollection {
    return {
        *[Symbol.iterator](): Iterator<Tracker> {
            yield* trackers;
        },
        isEmpty: () => trackers.length === 0,
    };
}
