/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { NewCardPayload, UpdateCardPayload } from "../store/swimlane/card/type";
import type {
    Field,
    Link,
    LinkField,
    ListField,
    PostBody,
    PutBody,
    TextField,
    TextValue,
    Values,
} from "../store/swimlane/card/api-artifact-type";
import { TEXT } from "../store/swimlane/card/api-artifact-type";
import type { AddInPlace, AssignedToField, Card, TitleField, Tracker, User } from "../type";

export function getPutArtifactBody(payload: UpdateCardPayload): PutBody {
    if (!payload.tracker.title_field) {
        throw new Error("Unable to update the card title");
    }

    const values: Field[] = [getTextFieldForLabel(payload.tracker.title_field, payload.label)];

    if (payload.tracker.assigned_to_field) {
        values.push(getListFieldForAssignee(payload.tracker.assigned_to_field, payload.assignees));
    }

    return {
        values,
    };
}

export function getPutArtifactBodyToAddChild(
    payload: NewCardPayload,
    trackers: Tracker[],
    child_id: number,
    parent_artifact_values: Values,
): PutBody {
    const parent_tracker = getParentTracker(payload.swimlane.card, trackers);
    if (!parent_tracker.add_in_place) {
        throw new Error("Unable to add in place");
    }

    return {
        values: [getLinkField(parent_tracker.add_in_place, child_id, parent_artifact_values)],
    };
}

export function getPostArtifactBody(payload: NewCardPayload, trackers: Tracker[]): PostBody {
    const parent_tracker = getParentTracker(payload.swimlane.card, trackers);
    if (!parent_tracker.add_in_place) {
        throw new Error("Unable to add in place");
    }

    const child_tracker = trackers.find(
        (tracker) =>
            parent_tracker.add_in_place &&
            tracker.id === parent_tracker.add_in_place.child_tracker_id,
    );
    if (!child_tracker) {
        throw new Error("Unable to find the child tracker of a card");
    }

    if (!child_tracker.title_field) {
        throw new Error("Unable to create the card");
    }

    const mapping = payload.column.mappings.find(
        (mapping) => mapping.tracker_id === child_tracker.id,
    );
    if (!mapping || !mapping.field_id || mapping.accepts.length === 0) {
        throw new Error("Unable to create the card");
    }
    const first_mapped_field_value_id = mapping.accepts[0].id;

    return {
        tracker: {
            id: mapping.tracker_id,
        },
        values: [
            getTextFieldForLabel(child_tracker.title_field, payload.label),
            getListField(mapping.field_id, first_mapped_field_value_id),
        ],
    };
}

function getParentTracker(card: Card, trackers: Tracker[]): Tracker {
    const parent_tracker = trackers.find((tracker) => tracker.id === card.tracker_id);
    if (!parent_tracker) {
        throw new Error("Unable to find the tracker of a card");
    }
    if (!parent_tracker.add_in_place) {
        throw new Error("Unable to add in place");
    }

    return parent_tracker;
}

function getTextFieldForLabel(title_field: TitleField, label: string): TextField {
    return getTextField(title_field, getValueForLabel(title_field, label));
}

function getListFieldForAssignee(assigned_to_field: AssignedToField, assignees: User[]): ListField {
    return {
        field_id: assigned_to_field.id,
        bind_value_ids: assignees.map((user) => user.id),
    };
}

function getTextField(title_field: TitleField, value: TextValue | string): TextField {
    return {
        field_id: title_field.id,
        value: value,
    };
}

function getListField(field_id: number, value_id: number): ListField {
    return {
        field_id: field_id,
        bind_value_ids: [value_id],
    };
}

function getLinkField(
    add_in_place: AddInPlace,
    child_id: number,
    parent_artifact_values: Values,
): LinkField {
    const existing_links_value = parent_artifact_values.find(
        (value) => value.field_id === add_in_place.parent_artifact_link_field_id,
    );
    let existing_links: Link[] = [];
    if (existing_links_value && "links" in existing_links_value) {
        existing_links = existing_links_value.links;
    }

    return {
        field_id: add_in_place.parent_artifact_link_field_id,
        links: [
            ...existing_links,
            {
                id: child_id,
                type: "_is_child",
            },
        ],
    };
}

function getValueForLabel(title_field: TitleField, label: string): string | TextValue {
    if (title_field.is_string_field) {
        return removeNewlines(label);
    }

    return forceTextFormatForTextField(label);
}

function removeNewlines(label: string): string {
    return label.replace(/(\r\n|\n|\r)+/gm, " ");
}

function forceTextFormatForTextField(label: string): TextValue {
    return {
        content: label,
        format: TEXT,
    };
}
