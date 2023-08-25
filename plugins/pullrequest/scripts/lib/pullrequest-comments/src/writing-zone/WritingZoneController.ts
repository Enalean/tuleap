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
    switchToPreviewMode(host: HostElement): void;
    focusWritingZone(host: HostElement): void;
    blurWritingZone(host: HostElement): void;
    resetWritingZone(host: HTMLElement & InternalWritingZone): void;
    initWritingZone(host: HostElement): void;
    setWritingZoneContent(host: HostElement, content: string): void;
    shouldFocusWritingZoneWhenConnected(): boolean;
};

export type WritingZoneConfig = {
    focus_writing_zone_when_connected?: boolean;
    is_comments_markdown_mode_enabled?: boolean;
};

export const WritingZoneController = (config: WritingZoneConfig): ControlWritingZone => {
    const focusWritingZone = (host: HostElement): void => {
        host.presenter = WritingZonePresenter.buildFocused(host.presenter);

        if (host.presenter.is_in_writing_mode) {
            host.textarea.focus();
            host.textarea.setSelectionRange(host.textarea.value.length, host.textarea.value.length);
        }

        if (host.parentElement) {
            host.parentElement.classList.add("pull-request-comment-with-writing-zone-active");
        }
    };

    const blurWritingZone = (host: HTMLElement & InternalWritingZone): void => {
        host.presenter = WritingZonePresenter.buildBlurred(host.presenter);

        if (host.presenter.is_in_writing_mode) {
            host.textarea.blur();
        }

        if (host.parentElement) {
            host.parentElement.classList.remove("pull-request-comment-with-writing-zone-active");
        }
    };

    return {
        initWritingZone: (host: HostElement): void => {
            host.presenter = WritingZonePresenter.buildInitial(
                config.is_comments_markdown_mode_enabled
            );
        },

        onTextareaInput: (host: HostElement): void => {
            dispatch(host, "writing-zone-input", {
                detail: {
                    content: host.textarea.value,
                },
            });
        },

        switchToWritingMode: (host: HostElement): void => {
            host.presenter = WritingZonePresenter.buildWritingMode(host.presenter);

            setTimeout(() => {
                focusWritingZone(host);
            });
        },

        switchToPreviewMode: (host: HostElement): void => {
            host.presenter = WritingZonePresenter.buildPreviewMode(host.presenter);

            setTimeout(() => {
                focusWritingZone(host);
            });
        },

        focusWritingZone,
        blurWritingZone,

        resetWritingZone: (host: HTMLElement & InternalWritingZone): void => {
            host.textarea.value = "";
            host.presenter = WritingZonePresenter.buildBlurred(
                WritingZonePresenter.buildWritingMode(host.presenter)
            );
            blurWritingZone(host);
        },

        setWritingZoneContent: (host: HostElement, content: string): void => {
            host.presenter = WritingZonePresenter.buildWithContent(host.presenter, content);
        },

        shouldFocusWritingZoneWhenConnected: () => config.focus_writing_zone_when_connected ?? true,
    };
};
