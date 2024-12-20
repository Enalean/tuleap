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

import type { Ref } from "vue";
import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { isArtifactSection, isFreetextSection } from "@/helpers/artidoc-section.type";
import type { ArtifactSection, FreetextSection } from "@/helpers/artidoc-section.type";
import { reorderSections } from "@/helpers/rest-querier";
import type { InternalArtidocSectionId, StoredArtidocSection } from "@/stores/useSectionsStore";
import { CannotReorderSectionsFault } from "@/stores/CannotReorderSectionsFault";

export type SectionsReorderer = {
    moveSectionUp(document_id: number, section: StoredArtidocSection): ResultAsync<unknown, Fault>;
    moveSectionDown(
        document_id: number,
        section: StoredArtidocSection,
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
    sections: Ref<StoredArtidocSection[] | undefined>,
): SectionsReorderer => {
    function getNextArtifacOrFreetextSection(
        start: number,
    ): Option<ArtifactSection | FreetextSection> {
        if (sections.value === undefined) {
            return Option.nothing();
        }

        for (let i = start; i < sections.value.length; i++) {
            const next_section = sections.value[i];
            if (isArtifactSection(next_section) || isFreetextSection(next_section)) {
                return Option.fromValue(next_section);
            }
        }

        return Option.nothing();
    }

    function getPreviousArtifactOrFreetextSection(
        start: number,
    ): Option<ArtifactSection | FreetextSection> {
        if (sections.value === undefined) {
            return Option.nothing();
        }

        for (let i = start; i >= 0; i--) {
            const previous_section = sections.value[i];
            if (isArtifactSection(previous_section) || isFreetextSection(previous_section)) {
                return Option.fromValue(previous_section);
            }
        }

        return Option.nothing();
    }

    function moveArtifactOrFreetextSectionBeforeSibling(
        moved_section_index: number,
        document_id: number,
        section: StoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        if (!isArtifactSection(section) && !isFreetextSection(section)) {
            return okAsync(null);
        }

        const next_section = getNextArtifacOrFreetextSection(moved_section_index).unwrapOr(null);
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
        if (!isArtifactSection(section) && !isFreetextSection(section)) {
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
        section: StoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section).match(
            (index): ResultAsync<unknown, Fault> => {
                if (sections.value === undefined) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                if (index === 0) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                const sections_before_move = [...sections.value];

                sections.value.splice(index, 1);
                sections.value.splice(index - 1, 0, section);

                return moveArtifactOrFreetextSectionBeforeSibling(
                    index,
                    document_id,
                    section,
                ).mapErr((fault) => {
                    sections.value = sections_before_move;

                    return fault;
                });
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function moveSectionDown(
        document_id: number,
        section: StoredArtidocSection,
    ): ResultAsync<unknown, Fault> {
        return findIndexOfSection(section).match(
            (index) => {
                if (sections.value === undefined) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                if (index >= sections.value.length - 1) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                const sections_before_move = [...sections.value];

                sections.value.splice(index, 1);
                sections.value.splice(index + 1, 0, section);

                return moveArtifactOrFreetextSectionAfterSibling(
                    index,
                    document_id,
                    section,
                ).mapErr((fault) => {
                    sections.value = sections_before_move;

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
                        if (sections.value === undefined) {
                            throw new Error("Sections are undefined. It should not happen.");
                        }

                        if (sections.value.length - 1 < index_section) {
                            return errAsync(CannotReorderSectionsFault.build());
                        }

                        if (sections.value.length - 1 < index_sibling) {
                            return errAsync(CannotReorderSectionsFault.build());
                        }

                        if (index_sibling === index_section + 1) {
                            // same position, do nothing
                            return okAsync(null);
                        }

                        const section = sections.value[index_section];

                        const sections_before_move = [...sections.value];

                        const new_section_index =
                            index_section > index_sibling ? index_sibling : index_sibling - 1;

                        sections.value.splice(index_section, 1);
                        sections.value.splice(new_section_index, 0, section);

                        return moveArtifactOrFreetextSectionBeforeSibling(
                            new_section_index + 1,
                            document_id,
                            section,
                        ).mapErr((fault) => {
                            sections.value = sections_before_move;

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
                if (sections.value === undefined) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                if (sections.value.length - 1 < index_section) {
                    return errAsync(CannotReorderSectionsFault.build());
                }

                if (index_section === sections.value.length - 1) {
                    // same position, do nothing
                    return okAsync(null);
                }

                const sections_before_move = [...sections.value];
                const section = sections.value[index_section];

                sections.value.splice(index_section, 1);
                sections.value.push(section);

                const penultimate_index = sections.value.length - 2;
                return moveArtifactOrFreetextSectionAfterSibling(
                    penultimate_index,
                    document_id,
                    section,
                ).mapErr((fault) => {
                    sections.value = sections_before_move;
                    return fault;
                });
            },
            () => errAsync(CannotReorderSectionsFault.build()),
        );
    }

    function findIndexOfSection(section: InternalArtidocSectionId): Option<number> {
        if (sections.value === undefined) {
            return Option.nothing();
        }

        const index = sections.value.findIndex(
            (element) => element.internal_id === section.internal_id,
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
