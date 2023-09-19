/*
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

import {
    TEXT_FORMAT_HTML,
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
} from "@tuleap/plugin-tracker-constants";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

const DEFAULT_LOCALE = "en_US";
const FORMAT_HIDDEN_INPUT_ID_PREFIX = "tracker_artifact_followup_comment_body_format_";
const COMMENT_BODY_SELECTOR = ".tracker_artifact_followup_comment_body";

export function getFormatOrDefault(doc: Document, changeset_id: string): TextFieldFormat {
    const input_id = FORMAT_HIDDEN_INPUT_ID_PREFIX + changeset_id;
    const format_hidden_input = doc.getElementById(input_id);
    if (!(format_hidden_input instanceof HTMLInputElement)) {
        // There is no hidden input if I'm editing a follow-up without comment
        return TEXT_FORMAT_COMMONMARK;
    }
    return isValidTextFormat(format_hidden_input.value)
        ? format_hidden_input.value
        : TEXT_FORMAT_COMMONMARK;
}

export function getTextAreaValue(comment_panel: Element, format: TextFieldFormat): string {
    const comment_body = comment_panel.querySelector(COMMENT_BODY_SELECTOR);
    if (!(comment_body instanceof HTMLElement)) {
        // There is no comment body if I'm editing a follow-up without comment
        return "";
    }
    if (format === TEXT_FORMAT_COMMONMARK) {
        return comment_body.dataset.commonmarkSource ?? "";
    }
    if (format === TEXT_FORMAT_HTML) {
        return comment_body.innerHTML.trim();
    }
    // Text format
    return comment_body.textContent?.trim() ?? "";
}

export function getProjectId(followup_body: HTMLElement): string {
    const project_id = followup_body.dataset.projectId;
    if (project_id === undefined) {
        throw new Error("Could not find the data-project-id attribute from the followup content");
    }
    return project_id;
}

export function getLocaleFromBody(doc: Document): string {
    const locale = doc.body.dataset.userLocale;
    return locale ?? DEFAULT_LOCALE;
}
