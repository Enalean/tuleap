/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

const COLLAPSE_THRESHOLD = 10;

export {
    getCollapsibleCodeSections,
    getUnchangedSections,
    getPaddedCollapsibleSections,
    isThereACommentOnThisLine,
    getCollapsibleSectionsSideBySide,
};

function isThereACommentOnThisLine(line_number, inline_comments) {
    return Boolean(inline_comments.find((comment) => comment.unidiff_offset === line_number));
}

function getUnchangedSections(code, inline_comments) {
    const collapsible_sections = [];

    let next_section_beginning = 0;
    let nb_lines_unchanged = 0;
    let previous_line_offsets = 0;

    code.forEach(({ old_offset, new_offset, unidiff_offset }, line_number) => {
        const line_offset = Math.abs(new_offset - old_offset);
        const is_line_commented = isThereACommentOnThisLine(unidiff_offset, inline_comments);

        if (line_offset === previous_line_offsets && !is_line_commented) {
            nb_lines_unchanged++;

            return;
        }

        if (is_line_commented) {
            let end = line_number;

            if (end === code.length - 1) {
                end--;
            }

            collapsible_sections.push({
                start: next_section_beginning,
                end,
            });

            next_section_beginning = line_number + 1;
        } else if (nb_lines_unchanged >= COLLAPSE_THRESHOLD) {
            collapsible_sections.push({
                start: next_section_beginning,
                end: line_number - 1,
            });

            next_section_beginning = line_number;
        } else {
            next_section_beginning = line_number;
        }

        previous_line_offsets = line_offset;
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

function getPaddedCollapsibleSections(collapsible_sections, file_length) {
    const sections_to_collapse = [];

    collapsible_sections.forEach(({ start, end }) => {
        if (end - start < 1) {
            return;
        }

        if (start === 0 && end - COLLAPSE_THRESHOLD > start) {
            sections_to_collapse.push({
                start,
                end: end - COLLAPSE_THRESHOLD,
            });

            return;
        }

        if (end === file_length && end > start + COLLAPSE_THRESHOLD) {
            sections_to_collapse.push({
                start: start + COLLAPSE_THRESHOLD,
                end,
            });

            return;
        }

        if (start + COLLAPSE_THRESHOLD < end - COLLAPSE_THRESHOLD) {
            sections_to_collapse.push({
                start: start + COLLAPSE_THRESHOLD,
                end: end - COLLAPSE_THRESHOLD,
            });
        }
    });

    return sections_to_collapse;
}

function getDiffSideBySideSection(line_start, line_end) {
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

function getCollapsibleSectionsSideBySide(code, comments) {
    return getCollapsibleCodeSections(code, comments).map((section) => {
        let line_start, line_end;

        if (section.start === 0) {
            line_start = { old_offset: 0, new_offset: 0 };
            line_end = code.find((line) => line.unidiff_offset === section.end);

            return getDiffSideBySideSection(line_start, line_end);
        }

        line_start = code.find((line) => line.unidiff_offset === section.start);
        line_end = code.find((line) => line.unidiff_offset === section.end);

        return getDiffSideBySideSection(line_start, line_end);
    });
}

function getCollapsibleCodeSections(code, inline_comments) {
    return getPaddedCollapsibleSections(
        getUnchangedSections(code, inline_comments),
        code.length - 1
    );
}
