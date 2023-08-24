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
    readonly is_focused: boolean;
    readonly is_in_writing_mode: boolean;
}

export const WritingZonePresenter = {
    buildInitial: (): WritingZonePresenter => ({
        initial_content: "",
        is_focused: false,
        is_in_writing_mode: true,
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
    }),
    buildWithContent: (presenter: WritingZonePresenter, content: string): WritingZonePresenter => ({
        ...presenter,
        initial_content: content,
    }),
};
