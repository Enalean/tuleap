/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

interface ArtifactFieldValueRepresentation {
    readonly field_id: number;
    readonly label: string;
}

interface ArtifactFieldValueStringRepresentation extends ArtifactFieldValueRepresentation {
    readonly type: "string";
    readonly value: string;
}

interface ArtifactFieldValueTextRepresentation extends ArtifactFieldValueRepresentation {
    readonly type: "text";
    readonly value: string;
    readonly format: string;
    readonly post_processed_value: string;
}

export interface ArtifactFieldValueCommonmarkRepresentation
    extends ArtifactFieldValueTextRepresentation {
    readonly commonmark: string;
}

export type ArtifactTextFieldValueRepresentation =
    | ArtifactFieldValueCommonmarkRepresentation
    | ArtifactFieldValueTextRepresentation;

export type ArtidocSection = {
    id: string;
    artifact: {
        id: number;
        uri: string;
        tracker: {
            id: number;
            uri: string;
            label: string;
            project: {
                id: number;
                uri: string;
                label: string;
                icon: string;
            };
        };
    };
    title: ArtifactFieldValueStringRepresentation | ArtifactTextFieldValueRepresentation;
    display_title: string;
    description: ArtifactTextFieldValueRepresentation;
    can_user_edit_section: boolean;
};

export function isTitleAString(
    title: ArtidocSection["title"],
): title is ArtifactFieldValueStringRepresentation {
    return title.type === "string";
}

export function isCommonmark(
    value: ArtifactTextFieldValueRepresentation,
): value is ArtifactFieldValueCommonmarkRepresentation {
    return "commonmark" in value;
}
