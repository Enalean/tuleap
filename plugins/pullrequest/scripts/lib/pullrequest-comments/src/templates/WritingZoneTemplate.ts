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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { GettextProvider } from "@tuleap/gettext";
import type { FocusTextArea } from "../helpers/textarea-focus-helper";
import { FOCUSABLE_TEXTAREA_CLASSNAME } from "../helpers/textarea-focus-helper";

export interface WritingZoneState {
    readonly initial_content: string;
    readonly is_focused: boolean;
}

export type onWritingZoneContentChangeCallbackType = (new_content: string) => void;
export type onWritingZoneStateChangeCallbackType = (is_focused: boolean) => void;

export const getWritingZoneTemplate = (
    state: WritingZoneState,
    focus_helper: FocusTextArea,
    onTextAreaChange: onWritingZoneContentChangeCallbackType,
    onFocusChange: onWritingZoneStateChangeCallbackType,
    gettext_provider: GettextProvider
): UpdateFunction<HTMLElement> => {
    const onTextareaInput = (host: HTMLElement, event: InputEvent): void => {
        const target = event.target;
        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }

        onTextAreaChange(target.value);
    };

    const onTextareaFocus = (): void => {
        onFocusChange(true);
    };

    const onTextareaBlur = (): void => {
        onFocusChange(false);
    };

    const tabs_classes = {
        "tlp-tab": true,
        "tlp-tab-active": state.is_focused,
    };

    return html`
        <div class="pull-request-comment-write-mode-header">
            <div class="tlp-tabs pull-request-comment-write-mode-header-tabs">
                <span
                    data-test="writing-tab"
                    class="${tabs_classes}"
                    onclick="${focus_helper.focusTextArea}"
                >
                    ${gettext_provider.gettext("Write")}
                </a>
            </div>
        </div>
        <textarea
            data-test="writing-zone-textarea"
            class="${FOCUSABLE_TEXTAREA_CLASSNAME} tlp-textarea"
            rows="10"
            placeholder="${gettext_provider.gettext("Say something…")}"
            oninput="${onTextareaInput}"
            onfocus="${onTextareaFocus}"
            onblur="${onTextareaBlur}"
        >${state.initial_content}</textarea>
    `;
};
