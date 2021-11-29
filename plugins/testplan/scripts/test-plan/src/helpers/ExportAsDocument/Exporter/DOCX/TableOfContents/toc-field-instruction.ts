/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

/**
 * This mainly an extracted from https://github.com/dolanmiu/docx/blob/6.0.3/src/file/table-of-contents/field-instruction.ts
 */

import type { ITableOfContentsOptions } from "docx";
import { XmlComponent } from "docx";
import { TextAttributes } from "../base-elements";

export class TOCFieldInstruction extends XmlComponent {
    constructor(properties: ITableOfContentsOptions = {}) {
        super("w:instrText");

        this.root.push(new TextAttributes({ space: "default" }));
        let instruction = "TOC";

        if (properties.captionLabel) {
            instruction = `${instruction} \\a "${properties.captionLabel}"`;
        }
        if (properties.entriesFromBookmark) {
            instruction = `${instruction} \\b "${properties.entriesFromBookmark}"`;
        }
        if (properties.captionLabelIncludingNumbers) {
            instruction = `${instruction} \\c "${properties.captionLabelIncludingNumbers}"`;
        }
        if (properties.sequenceAndPageNumbersSeparator) {
            instruction = `${instruction} \\d "${properties.sequenceAndPageNumbersSeparator}"`;
        }
        if (properties.tcFieldIdentifier) {
            instruction = `${instruction} \\f "${properties.tcFieldIdentifier}"`;
        }
        if (properties.hyperlink) {
            instruction = `${instruction} \\h`;
        }
        if (properties.tcFieldLevelRange) {
            instruction = `${instruction} \\l "${properties.tcFieldLevelRange}"`;
        }
        if (properties.pageNumbersEntryLevelsRange) {
            instruction = `${instruction} \\n "${properties.pageNumbersEntryLevelsRange}"`;
        }
        if (properties.headingStyleRange) {
            instruction = `${instruction} \\o "${properties.headingStyleRange}"`;
        }
        if (properties.entryAndPageNumberSeparator) {
            instruction = `${instruction} \\p "${properties.entryAndPageNumberSeparator}"`;
        }
        if (properties.seqFieldIdentifierForPrefix) {
            instruction = `${instruction} \\s "${properties.seqFieldIdentifierForPrefix}"`;
        }
        if (properties.stylesWithLevels && properties.stylesWithLevels.length) {
            const styles = properties.stylesWithLevels
                .map((sl) => `${sl.styleName},${sl.level}`)
                .join(",");
            instruction = `${instruction} \\t "${styles}"`;
        }
        if (properties.useAppliedParagraphOutlineLevel) {
            instruction = `${instruction} \\u`;
        }
        if (properties.preserveTabInEntries) {
            instruction = `${instruction} \\w`;
        }
        if (properties.preserveNewLineInEntries) {
            instruction = `${instruction} \\x`;
        }
        if (properties.hideTabAndPageNumbersInWebView) {
            instruction = `${instruction} \\z`;
        }
        this.root.push(instruction);
    }
}
