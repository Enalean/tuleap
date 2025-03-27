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
 *
 */

export interface Mapping {
    readonly tracker_id: number;
    readonly field_id: number | null;
    readonly accepts: Array<ListValue>;
}

export interface ListValue {
    id: number;
}

export interface ColumnDefinition {
    id: number;
    label: string;
    color: string;
    mappings: Array<Mapping>;
    is_collapsed: boolean;
    has_hover: boolean;
}

export interface Swimlane {
    card: Card;
    children_cards: Array<Card>;
    is_loading_children_cards: boolean;
}

export interface MappedListValue extends ListValue {
    label: string;
}

export interface RemainingEffort {
    value: number | null;
    can_update: boolean;
    is_in_edit_mode: boolean;
    is_being_saved: boolean;
}

export interface ArtifactLinkField {
    readonly id: number;
}

export interface TitleField {
    readonly id: number;
    readonly is_string_field: boolean;
}

export interface AssignedToField {
    readonly id: number;
    readonly is_multiple: boolean;
}

export interface AddInPlace {
    child_tracker_id: number;
    parent_artifact_link_field_id: number;
}

export interface Tracker {
    readonly id: number;
    readonly can_update_mapped_field: boolean;
    readonly title_field: TitleField | null;
    readonly assigned_to_field: AssignedToField | null;
    readonly artifact_link_field: ArtifactLinkField | null;
    readonly add_in_place_tracker_id: AddInPlace | null;
    readonly add_in_place: AddInPlace | null;
}

export interface Card {
    id: number;
    tracker_id: number;
    label: string;
    xref: string;
    rank: number;
    color: string;
    background_color: string;
    artifact_html_uri: string;
    assignees: Array<User>;
    has_children: boolean;
    mapped_list_value: MappedListValue | null;
    initial_effort: number | null;
    remaining_effort: RemainingEffort | null;
    is_open: boolean;
    is_collapsed: boolean;
    is_in_edit_mode: boolean;
    is_being_saved: boolean;
    is_just_saved: boolean;
}

interface UserProperties {
    avatar_url: string;
    display_name: string;
}

export interface User extends UserProperties {
    id: number;
}

export interface CardPosition {
    ids: number[];
    direction: Direction;
    compared_to: number;
}

export type Direction = "before" | "after";
export const BEFORE: Direction = "before";
export const AFTER: Direction = "after";

export const TaskboardEvent = {
    CANCEL_CARD_EDITION: "cancel-card-edition",
    SAVE_CARD_EDITION: "save-card-edition",
    ESC_KEY_PRESSED: "esc-key-pressed",
};

export type ArrowKey = typeof DOWN | typeof UP | typeof RIGHT | typeof LEFT;
export const DOWN = "DOWN";
export const UP = "UP";
export const RIGHT = "RIGHT";
export const LEFT = "LEFT";

export interface GettextProvider {
    $gettext: (msgid: string) => string;
    $pgettext: (context: string, msgid: string) => string;
}

export type NavigationElement = typeof SWIMLANE | typeof CARD | typeof ADDFORM | typeof CELL;
export const SWIMLANE = "swimlane";
export const CARD = "card";
export const ADDFORM = "add-form";
export const CELL = "cell";
