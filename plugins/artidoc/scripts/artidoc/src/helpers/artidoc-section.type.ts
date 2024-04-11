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

interface ArtifactFieldValueTextRepresentation {
    readonly field_id: number;
    readonly type: string;
    readonly label: string;
    readonly value: string;
    readonly format: string;
    readonly post_processed_value: string;
}

interface ArtifactFieldValueCommonmarkRepresentation extends ArtifactFieldValueTextRepresentation {
    readonly commonmark: string;
}

type ArtifactTextFieldValueRepresentation =
    | ArtifactFieldValueCommonmarkRepresentation
    | ArtifactFieldValueTextRepresentation;

export type ArtidocSection = {
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
    title: string;
    description: ArtifactTextFieldValueRepresentation;
};
