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

import { dispatch } from "hybrids";
import type { HostElement, InternalWritingZone } from "./WritingZone";
import { WritingZonePresenter } from "./WritingZonePresenter";

export type ControlWritingZone = {
    onTextareaInput(host: HostElement): void;
    switchToWritingMode(host: HostElement): void;
    focusTextArea(host: HostElement): void;
    blurTextArea(host: HostElement): void;
    resetTextArea(host: HTMLElement & InternalWritingZone): void;
    initWritingZone(host: HostElement): void;
    setWritingZoneContent(host: HostElement, content: string): void;
    shouldFocusWritingZoneWhenConnected(): boolean;
};

type WritingZoneConfig = {
    focus_writing_zone_when_connected?: boolean;
};

export const WritingZoneController = (config: WritingZoneConfig): ControlWritingZone => {
    const focusTextArea = (host: HostElement): void => {
        host.presenter = WritingZonePresenter.buildFocused(host.presenter);
        host.textarea.focus();
        host.textarea.setSelectionRange(host.textarea.value.length, host.textarea.value.length);

        if (host.parentElement) {
            host.parentElement.classList.add("pull-request-comment-with-writing-zone-active");
        }
    };

    const blurTextArea = (host: HTMLElement & InternalWritingZone): void => {
        host.presenter = WritingZonePresenter.buildBlurred(host.presenter);
        host.textarea.blur();

        if (host.parentElement) {
            host.parentElement.classList.remove("pull-request-comment-with-writing-zone-active");
        }
    };

    return {
        initWritingZone: (host: HostElement): void => {
            host.presenter = WritingZonePresenter.buildInitial();
        },

        onTextareaInput: (host: HostElement): void => {
            dispatch(host, "writing-zone-input", {
                detail: {
                    content: host.textarea.value,
                },
            });
        },

        switchToWritingMode: (host: HostElement): void => {
            focusTextArea(host);

            host.presenter = WritingZonePresenter.buildWritingMode(host.presenter);
        },

        focusTextArea,
        blurTextArea,

        resetTextArea: (host: HTMLElement & InternalWritingZone): void => {
            host.textarea.value = "";
            host.presenter = WritingZonePresenter.buildBlurred(host.presenter);
            blurTextArea(host);
        },

        setWritingZoneContent: (host: HostElement, content: string): void => {
            host.presenter = WritingZonePresenter.buildWithContent(host.presenter, content);
        },

        shouldFocusWritingZoneWhenConnected: () => config.focus_writing_zone_when_connected ?? true,
    };
};
