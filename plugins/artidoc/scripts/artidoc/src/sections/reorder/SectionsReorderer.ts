/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { isPendingSection } from "@/helpers/artidoc-section.type";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { reorderSections } from "@/helpers/rest-querier";
import type {
    InternalArtidocSectionId,
    SectionsCollection,
    StoredArtidocSection,
    ReactiveStoredArtidocSection,
} from "@/sections/SectionsCollection";
import { CannotReorderSectionsFault } from "@/sections/reorder/CannotReorderSectionsFault";

export type SectionsReorderer = {
    moveSectionUp(
        document_id: number,
        section: ReactiveStoredArtidocSection,
    ): ResultAsync<unknown, Fault>;
    moveSectionDown(
        document_id: number,
        section: ReactiveStoredArtidocSection,
    ): ResultAsync<unknown, Fault>;
    moveSectionBefore(
        document_id: number,
        section: InternalArtidocSectionId,
        next_sibling: InternalArtidocSectionId,
    ): ResultAsync<unknown, Fault>;
    moveSectionAtTheEnd(
        document_id: number,
        section: InternalArtidocSectionId,
    ): ResultAsync<unknown, Fault>;
};

export const buildSectionsReorderer = (
    sections_collection: SectionsCollection,
): SectionsReorderer => {
    function getNextArtifactOrFreetextSection(start: number): Option<ArtidocSection> {
        for (let i = start; i < sections_collection.sections.value.length; i++) {
            const next_section = sections_collection.sections.value[i];
            if (!isPendingSection(next_section.value)) {
                return Option.fromValue(next_section.value);
            }
        }

        return Option.nothing();
    }

    function getPreviousArtifactOrFreetextSection(start: number): Option<ArtidocSection> {
        for (let i = start; i >= 0; i--) {
            const previous_section = sections_collection.sections.value[i];
            if (!isPendingSection(previous_section.value)) {
                return Option.fromValue(previous_section.value);
            }
        }

        return Option.nothing();
    }

    function moveArtifactOrFreetextSectionBeforeSibling(
        moved_section_index: number,
        document_id: number,
        section: StoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        if (isPendingSection(section)) {
            return okAsync(null);
        }

        const next_section = getNextArtifactOrFreetextSection(moved_section_index).unwrapOr(null);
        if (!next_section) {
            return okAsync(null);
        }

        return reorderSections(document_id, section.id, "before", next_section.id);
    }

    function moveArtifactOrFreetextSectionAfterSibling(
        moved_section_index: number,
        document_id: number,
        section: StoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        if (isPendingSection(section)) {
            return okAsync(null);
        }

        const previous_section =
            getPreviousArtifactOrFreetextSection(moved_section_index).unwrapOr(null);
        if (!previous_section) {
            return okAsync(null);
        }

        return reorderSections(document_id, section.id, "after", previous_section.id);
    }

    function moveSectionUp(
        document_id: number,
        section: ReactiveStoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section.value).match(
            (index): ResultAsync<unknown, Fault> => {
                if (index === 0) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                const sections_before_move = [...sections_collection.sections.value];

                sections_collection.sections.value.splice(index, 1);
                sections_collection.sections.value.splice(index - 1, 0, section);

                return moveArtifactOrFreetextSectionBeforeSibling(
                    index,
                    document_id,
                    section.value,
                ).mapErr((fault) => {
                    sections_collection.sections.value = sections_before_move;

                    return fault;
                });
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function moveSectionDown(
        document_id: number,
        section: ReactiveStoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section.value).match(
            (index) => {
                if (index >= sections_collection.sections.value.length - 1) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                const sections_before_move = [...sections_collection.sections.value];

                sections_collection.sections.value.splice(index, 1);
                sections_collection.sections.value.splice(index + 1, 0, section);

                return moveArtifactOrFreetextSectionAfterSibling(
                    index,
                    document_id,
                    section.value,
                ).mapErr((fault) => {
                    sections_collection.sections.value = sections_before_move;

                    return fault;
                });
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function moveSectionBefore(
        document_id: number,
        section: InternalArtidocSectionId,
        next_sibling: InternalArtidocSectionId,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section).match(
            (index_section) => {
                return findIndexOfSection(next_sibling).match(
                    (index_sibling) => {
                        if (sections_collection.sections.value.length - 1 < index_section) {
                            return errAsync(CannotReorderSectionsFault.build());
                        }

                        if (sections_collection.sections.value.length - 1 < index_sibling) {
                            return errAsync(CannotReorderSectionsFault.build());
                        }

                        if (index_sibling === index_section + 1) {
                            // same position, do nothing
                            return okAsync(null);
                        }

                        const section = sections_collection.sections.value[index_section];

                        const sections_before_move = [...sections_collection.sections.value];

                        const new_section_index =
                            index_section > index_sibling ? index_sibling : index_sibling - 1;

                        sections_collection.sections.value.splice(index_section, 1);
                        sections_collection.sections.value.splice(new_section_index, 0, section);

                        return moveArtifactOrFreetextSectionBeforeSibling(
                            new_section_index + 1,
                            document_id,
                            section.value,
                        ).mapErr((fault) => {
                            sections_collection.sections.value = sections_before_move;

                            return fault;
                        });
                    },
                    () => errAsync(CannotReorderSectionsFault.build()),
                );
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function moveSectionAtTheEnd(
        document_id: number,
        section: InternalArtidocSectionId,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section).match(
            (index_section) => {
                if (sections_collection.sections.value.length - 1 < index_section) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                if (index_section === sections_collection.sections.value.length - 1) {
                    // same position, do nothing
                    return okAsync(null);
                }

                const sections_before_move = [...sections_collection.sections.value];
                const section = sections_collection.sections.value[index_section];

                sections_collection.sections.value.splice(index_section, 1);
                sections_collection.sections.value.push(section);

                const penultimate_index = sections_collection.sections.value.length - 2;
                return moveArtifactOrFreetextSectionAfterSibling(
                    penultimate_index,
                    document_id,
                    section.value,
                ).mapErr((fault) => {
                    sections_collection.sections.value = sections_before_move;
                    return fault;
                });
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function findIndexOfSection(section: InternalArtidocSectionId): Option<number> {
        const index = sections_collection.sections.value.findIndex(
            (element) => element.value.internal_id === section.internal_id,
        );
        if (index === -1) {
            return Option.nothing();
        }

        return Option.fromValue(index);
    }

    return {
        moveSectionUp,
        moveSectionDown,
        moveSectionBefore,
        moveSectionAtTheEnd,
    };
};
