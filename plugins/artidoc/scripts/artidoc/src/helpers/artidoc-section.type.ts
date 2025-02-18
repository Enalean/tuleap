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

import type { Tracker } from "@/stores/configuration-store";
import type { Project } from "@/helpers/project.type";
import type { Level } from "@/sections/levels/SectionsNumberer";

interface ArtifactFieldValueRepresentation {
    readonly field_id: number;
    readonly label: string;
}

interface ArtifactFieldValueTextRepresentation extends ArtifactFieldValueRepresentation {
    readonly type: "text";
    readonly value: string;
    readonly format: string;
    readonly post_processed_value: string;
}

interface FileInfoRepresentation {
    id: number;
    type: string;
}

export interface ArtifactFieldValueFileFullRepresentation extends ArtifactFieldValueRepresentation {
    type: "file";
    file_descriptions: FileInfoRepresentation[];
}

export interface ArtifactFieldValueCommonmarkRepresentation
    extends ArtifactFieldValueTextRepresentation {
    readonly commonmark: string;
}

export type ArtifactTextFieldValueRepresentation =
    | ArtifactFieldValueCommonmarkRepresentation
    | ArtifactFieldValueTextRepresentation;

export type ArtidocSection = FreetextSection | SectionBasedOnArtifact;

export type FreetextSection = {
    readonly type: "freetext";
    id: string;
    title: string;
    description: string;
    attachments: null;
    is_pending: boolean;
    level: Level;
    display_level: string;
};

export type SectionBasedOnArtifact = {
    readonly type: "artifact";
    id: string;
    title: string;
    description: string;
    attachments: ArtifactFieldValueFileFullRepresentation | null;
    level: Level;
    display_level: string;
};

export interface ArtifactSection extends SectionBasedOnArtifact {
    artifact: {
        id: number;
        uri: string;
        tracker: {
            id: number;
            uri: string;
            label: string;
            color: string;
            project: Project;
        };
    };
    can_user_edit_section: boolean;
}

export type PendingArtifactSection = SectionBasedOnArtifact & {
    tracker: Tracker;
};

export type PendingFreetextSection = FreetextSection & {
    is_pending: true;
};

export function isPendingArtifactSection(
    section: ArtidocSection,
): section is PendingArtifactSection {
    return "tracker" in section;
}

export function isPendingFreetextSection(
    section: ArtidocSection,
): section is PendingFreetextSection {
    return "is_pending" in section && section.is_pending;
}

export function isPendingSection(
    section: ArtidocSection,
): section is PendingFreetextSection | PendingArtifactSection {
    return isPendingArtifactSection(section) || isPendingFreetextSection(section);
}

export function isArtifactSection(section: ArtidocSection): section is ArtifactSection {
    return "artifact" in section;
}

export function isSectionBasedOnArtifact(
    section: ArtidocSection,
): section is SectionBasedOnArtifact {
    return section.type === "artifact";
}

export function isFreetextSection(section: ArtidocSection): section is FreetextSection {
    return section.type === "freetext";
}
