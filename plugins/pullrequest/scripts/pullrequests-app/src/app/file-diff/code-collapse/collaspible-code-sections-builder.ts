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

import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { FileLine, UnMovedFileLine } from "../types";
import { isAnUnmovedLine } from "../file-lines/file-line-helper";

const COLLAPSE_THRESHOLD = 10;

export interface CollapsibleSection {
    readonly start: number;
    readonly end: number;
}

export interface SynchronizedCollapsibleSections {
    readonly left: CollapsibleSection;
    readonly right: CollapsibleSection;
}

export function isThereACommentOnThisLine(
    file_line: FileLine,
    inline_comments: readonly PullRequestInlineCommentPresenter[],
): boolean {
    return Boolean(
        inline_comments.find((comment) => comment.file.unidiff_offset === file_line.unidiff_offset),
    );
}

export function getUnchangedSections(
    code: readonly FileLine[],
    inline_comments: readonly PullRequestInlineCommentPresenter[],
): CollapsibleSection[] {
    const collapsible_sections = [];

    let next_section_beginning = 0;
    let nb_lines_unchanged = 0;

    code.forEach((line) => {
        const is_line_commented = isThereACommentOnThisLine(line, inline_comments);
        if (isAnUnmovedLine(line) && !is_line_commented) {
            nb_lines_unchanged++;

            return;
        }

        if (is_line_commented) {
            let end = line.unidiff_offset;
            if (end === code.length - 1) {
                end--;
            }

            collapsible_sections.push({
                start: next_section_beginning,
                end,
            });

            next_section_beginning = line.unidiff_offset;
        } else if (nb_lines_unchanged >= COLLAPSE_THRESHOLD) {
            collapsible_sections.push({
                start: next_section_beginning,
                end: line.unidiff_offset - 2,
            });

            next_section_beginning = line.unidiff_offset;
        } else {
            next_section_beginning = line.unidiff_offset;
        }

        nb_lines_unchanged = 0;
    });

    if (nb_lines_unchanged >= COLLAPSE_THRESHOLD) {
        collapsible_sections.push({
            start: next_section_beginning,
            end: code.length - 1,
        });
    }

    return collapsible_sections;
}

export function getPaddedCollapsibleSections(
    collapsible_sections: CollapsibleSection[],
    file_length: number,
): CollapsibleSection[] {
    const sections_to_collapse: CollapsibleSection[] = [];

    collapsible_sections.forEach((section) => {
        if (section.end - section.start < 1) {
            return;
        }

        if (section.start === 0 && section.end - COLLAPSE_THRESHOLD > section.start) {
            sections_to_collapse.push({
                start: section.start,
                end: section.end - COLLAPSE_THRESHOLD,
            });

            return;
        }

        if (section.end === file_length && section.end > section.start + COLLAPSE_THRESHOLD) {
            sections_to_collapse.push({
                start: section.start + COLLAPSE_THRESHOLD,
                end: section.end,
            });

            return;
        }

        if (section.start + COLLAPSE_THRESHOLD < section.end - COLLAPSE_THRESHOLD) {
            sections_to_collapse.push({
                start: section.start + COLLAPSE_THRESHOLD,
                end: section.end - COLLAPSE_THRESHOLD,
            });
        }
    });

    return sections_to_collapse;
}

function getDiffSideBySideSection(
    line_start: UnMovedFileLine,
    line_end: UnMovedFileLine,
): SynchronizedCollapsibleSections {
    return {
        left: {
            start: line_start.old_offset,
            end: line_end.old_offset,
        },
        right: {
            start: line_start.new_offset,
            end: line_end.new_offset,
        },
    };
}

export function getCollapsibleSectionsSideBySide(
    code: readonly FileLine[],
    comments: readonly PullRequestInlineCommentPresenter[],
): SynchronizedCollapsibleSections[] {
    const synchronized_sections: SynchronizedCollapsibleSections[] = [];

    getCollapsibleCodeSections(code, comments).forEach((section) => {
        const line_end: FileLine | undefined = code.find(
            (line) => line.unidiff_offset === section.end,
        );
        if (!isAnUnmovedLine(line_end)) {
            return;
        }

        if (section.start === 0) {
            synchronized_sections.push(
                getDiffSideBySideSection(
                    { old_offset: 0, new_offset: 0, unidiff_offset: 0, content: "" },
                    line_end,
                ),
            );
        }

        const line_start: FileLine | undefined = code.find(
            (line) => line.unidiff_offset === section.start,
        );
        if (!isAnUnmovedLine(line_start)) {
            return;
        }

        synchronized_sections.push(getDiffSideBySideSection(line_start, line_end));
    });

    return synchronized_sections;
}

export function getCollapsibleCodeSections(
    code: readonly FileLine[],
    inline_comments: readonly PullRequestInlineCommentPresenter[],
): CollapsibleSection[] {
    return getPaddedCollapsibleSections(
        getUnchangedSections(code, inline_comments),
        code.length - 1,
    );
}
