/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

export interface FocusTextArea {
    focusTextArea: (component_content: HTMLElement) => void;
    resetTextArea: (component_content: HTMLElement) => void;
}

export const FOCUSABLE_TEXTAREA_CLASSNAME = "pull-request-comment-textarea";

const getTextArea = (component_content: HTMLElement): HTMLTextAreaElement | null => {
    return component_content.querySelector<HTMLTextAreaElement>(`.${FOCUSABLE_TEXTAREA_CLASSNAME}`);
};

export const PullRequestCommentTextareaFocusHelper = (): FocusTextArea => ({
    focusTextArea: (component_content: HTMLElement): void => {
        const target_textarea = getTextArea(component_content);
        if (!target_textarea) {
            return;
        }

        target_textarea.focus();
        target_textarea.setSelectionRange(
            target_textarea.value.length,
            target_textarea.value.length
        );
    },
    resetTextArea: (component_content: HTMLElement): void => {
        const target_textarea = getTextArea(component_content);
        if (!target_textarea) {
            return;
        }

        target_textarea.value = "";
        target_textarea.blur();
    },
});
