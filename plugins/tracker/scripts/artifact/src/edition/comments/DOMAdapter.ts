/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { Option } from "@tuleap/option";
import { getAttributeOrThrow } from "@tuleap/dom";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    isValidTextFormat,
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";

const EDIT_COMMENT_BUTTON_SELECTOR = "[data-edit-comment-button]";
const FOLLOW_UP_SELECTOR = "[data-follow-up]";
const FOLLOW_UP_CONTENT_SELECTOR = "[data-follow-up-content]";
const READ_ONLY_COMMENT_SELECTOR = "[data-read-only-comment]";
const COMMENT_BODY_SELECTOR = "[data-comment-body]";
export const FORMAT_HIDDEN_INPUT_ID_PREFIX = "tracker_artifact_followup_comment_body_format_";
export const FORMAT_SELECTBOX_ID_PREFIX = "rte_format_selectbox";
export const TEXTAREA_ID_PREFIX = "tracker_followup_comment_edit_";

export const HIDE_CLASS = "artifact-comment-hide";
export const HIGHLIGHT_CLASS = "artifact-comment-highlight";

export type InitDataFromBackend = {
    readonly changeset_id: string;
    readonly project_id: string;
    readonly are_notifications_enabled: boolean;
    readonly user_preferred_format: TextFieldFormat;
    readonly follow_up_content: HTMLElement;
    readonly read_only_comment: HTMLElement;
};

export type TextAreaPresenter = {
    readonly id: string;
    readonly project_id: string;
    readonly comment_body: string;
};

export type DOMAdapter = {
    findEditCommentButtons(): ReadonlyArray<HTMLElement>;
    readInitDataFromBackend(edit_button: HTMLElement): Option<InitDataFromBackend>;
    createTextArea(presenter: TextAreaPresenter): HTMLTextAreaElement;
    readCommentFormatOrDefault(
        changeset_id: string,
        default_format: TextFieldFormat,
    ): TextFieldFormat;
    readCommentBodyOrDefault(follow_up_content: HTMLElement, format: TextFieldFormat): string;
    hide(element: HTMLElement): void;
    show(element: HTMLElement): void;
    highlight(element: HTMLElement): void;
};

const computeNumberOfRowsShown = (comment_body: string): number => {
    const nb_rows_content = comment_body.split("\n").length;
    return Math.max(5, nb_rows_content);
};

function readUserPreferredFormat(element: HTMLElement): TextFieldFormat {
    const format = getAttributeOrThrow(element, "data-user-preferred-format");
    return isValidTextFormat(format) ? format : TEXT_FORMAT_COMMONMARK;
}

export const DOMAdapter = (doc: Document): DOMAdapter => {
    return {
        findEditCommentButtons(): ReadonlyArray<HTMLElement> {
            const buttons: HTMLElement[] = [];
            return Array.from(doc.querySelectorAll(EDIT_COMMENT_BUTTON_SELECTOR)).reduce(
                (accumulator, button) => {
                    if (!(button instanceof HTMLElement)) {
                        return accumulator;
                    }
                    accumulator.push(button);
                    return accumulator;
                },
                buttons,
            );
        },

        readInitDataFromBackend(edit_button): Option<InitDataFromBackend> {
            const follow_up = edit_button.closest(FOLLOW_UP_SELECTOR);
            if (!(follow_up instanceof HTMLElement)) {
                return Option.nothing();
            }
            const follow_up_content = follow_up.querySelector(FOLLOW_UP_CONTENT_SELECTOR);
            if (!(follow_up_content instanceof HTMLElement)) {
                return Option.nothing();
            }
            const changeset_id = getAttributeOrThrow(follow_up_content, "data-changeset-id");
            const project_id = getAttributeOrThrow(follow_up_content, "data-project-id");
            const are_notifications_enabled = !follow_up_content.hasAttribute(
                "data-notifications-disabled",
            );
            const user_preferred_format = readUserPreferredFormat(follow_up_content);
            const read_only_comment = follow_up_content.querySelector(READ_ONLY_COMMENT_SELECTOR);
            if (!(read_only_comment instanceof HTMLElement)) {
                return Option.nothing();
            }

            const init_data_from_backend: InitDataFromBackend = {
                changeset_id,
                project_id,
                are_notifications_enabled,
                user_preferred_format,
                follow_up_content,
                read_only_comment,
            };
            return Option.fromValue(init_data_from_backend);
        },

        createTextArea(presenter): HTMLTextAreaElement {
            const textarea = doc.createElement("textarea");
            textarea.id = presenter.id;
            textarea.setAttribute("data-project-id", presenter.project_id);
            textarea.setAttribute("data-test", "edit-comment-textarea");
            textarea.rows = computeNumberOfRowsShown(presenter.comment_body);
            textarea.insertAdjacentText("afterbegin", presenter.comment_body);
            return textarea;
        },

        readCommentFormatOrDefault(input_id, default_format): TextFieldFormat {
            const format_input = doc.getElementById(input_id);
            if (
                !(
                    format_input instanceof HTMLInputElement ||
                    format_input instanceof HTMLSelectElement
                )
            ) {
                // There is no hidden input if I'm editing a follow-up without comment
                return default_format;
            }
            return isValidTextFormat(format_input.value)
                ? format_input.value
                : TEXT_FORMAT_COMMONMARK;
        },

        readCommentBodyOrDefault(follow_up_content, format): string {
            const comment_body = follow_up_content.querySelector(COMMENT_BODY_SELECTOR);
            if (!(comment_body instanceof HTMLElement)) {
                return "";
            }
            if (format === TEXT_FORMAT_TEXT) {
                return comment_body.textContent?.trim() ?? "";
            }
            if (format === TEXT_FORMAT_HTML) {
                return comment_body.innerHTML.trim();
            }
            // Commonmark format
            return comment_body.dataset.commonmarkSource ?? "";
        },

        hide: (element) => element.classList.add(HIDE_CLASS),
        show: (element) => element.classList.remove(HIDE_CLASS),
        highlight: (element) => element.classList.add(HIGHLIGHT_CLASS),
    };
};
