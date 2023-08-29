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

export interface WritingZonePresenter {
    readonly initial_content: string;
    readonly previewed_content: string;
    readonly project_id: number;
    readonly is_focused: boolean;
    readonly is_in_writing_mode: boolean;
    readonly is_in_preview_mode: boolean;
    readonly has_preview_error: boolean;
    readonly is_comments_markdown_mode_enabled: boolean;
}

export const WritingZonePresenter = {
    buildInitial: (
        project_id: number,
        is_comments_markdown_mode_enabled = false
    ): WritingZonePresenter => ({
        initial_content: "",
        previewed_content: "",
        project_id,
        is_focused: false,
        is_in_writing_mode: true,
        is_in_preview_mode: false,
        has_preview_error: false,
        is_comments_markdown_mode_enabled,
    }),
    buildFocused: (presenter: WritingZonePresenter): WritingZonePresenter => ({
        ...presenter,
        is_focused: true,
    }),
    buildBlurred: (presenter: WritingZonePresenter): WritingZonePresenter => ({
        ...presenter,
        is_focused: false,
    }),
    buildWritingMode: (presenter: WritingZonePresenter): WritingZonePresenter => ({
        ...presenter,
        is_focused: true,
        is_in_writing_mode: true,
        is_in_preview_mode: false,
    }),
    buildPreviewMode: (
        presenter: WritingZonePresenter,
        previewed_content: string
    ): WritingZonePresenter => ({
        ...presenter,
        is_focused: true,
        is_in_writing_mode: false,
        is_in_preview_mode: true,
        has_preview_error: false,
        previewed_content,
    }),
    buildWithContent: (presenter: WritingZonePresenter, content: string): WritingZonePresenter => ({
        ...presenter,
        initial_content: content,
    }),
    buildPreviewWithError: (presenter: WritingZonePresenter): WritingZonePresenter => ({
        ...presenter,
        is_focused: true,
        is_in_writing_mode: false,
        is_in_preview_mode: true,
        has_preview_error: true,
        previewed_content: "",
    }),
};
