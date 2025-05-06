/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

export interface EditionSwitcher {
    init(): void;
    submissionBarIsAlreadyActive(doc: Document): boolean;
    toggleSubmitArtifactBar(
        follow_up_comment_editor_instance: CKEDITOR.editor | null,
        editor_format_selectbox: HTMLSelectElement | null,
        follow_up_new_comment: HTMLElement | null,
        doc: Document,
    ): void;
}

export function initEditionSwitcher(): EditionSwitcher;
