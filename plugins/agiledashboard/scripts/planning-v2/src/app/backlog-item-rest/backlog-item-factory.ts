/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

type Status = "Open" | "Closed";

interface TrackerRepresentation {
    readonly id: number;
    readonly label: string;
    readonly uri: string;
}

interface ArtifactRepresentation {
    readonly tracker: TrackerRepresentation;
}

export interface BacklogItemRepresentation {
    readonly id: number;
    readonly label: string;
    readonly color: string;
    readonly has_children: boolean;
    readonly accept: {
        readonly trackers: TrackerRepresentation[];
    };
    readonly status: Status;
    readonly artifact: ArtifactRepresentation;
    readonly short_type: string;
}

interface AcceptedTypes {
    readonly content: TrackerRepresentation[];
    readonly toString: () => string;
}

export interface BacklogItem extends BacklogItemRepresentation {
    updating: boolean;
    shaking: boolean;
    selected: boolean;
    hidden: boolean;
    multiple: boolean;
    moving_to: boolean;
    children: {
        data: BacklogItem[];
        loaded: boolean;
        collapsed: boolean;
    };
    readonly isOpen: () => boolean;
    readonly trackerId: string;
    readonly accepted_types: AcceptedTypes;
}

const getTrackerType = (tracker_id: number): string => "trackerId".concat(tracker_id.toString(10));

const formatAcceptedChildren = (allowed_trackers: TrackerRepresentation[]): AcceptedTypes => {
    return {
        content: allowed_trackers,
        toString(): string {
            return this.content
                .map((allowed_tracker) => "trackerId" + allowed_tracker.id)
                .join("|");
        },
    };
};

export function augment(backlog_item: BacklogItemRepresentation): BacklogItem {
    return {
        ...backlog_item,
        updating: false,
        shaking: false,
        selected: false,
        hidden: false,
        multiple: false,
        moving_to: false,
        children: {
            data: [],
            loaded: false,
            collapsed: true,
        },
        isOpen(): boolean {
            return this.status === "Open";
        },
        trackerId: getTrackerType(backlog_item.artifact.tracker.id),
        accepted_types: formatAcceptedChildren(backlog_item.accept.trackers),
    };
}
