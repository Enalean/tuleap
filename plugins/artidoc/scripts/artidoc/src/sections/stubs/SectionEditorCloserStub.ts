/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type { CloseSectionEditor } from "@/sections/editors/SectionEditorCloser";

export type CloseSectionEditorStub = CloseSectionEditor & {
    hasEditorBeenClosed(): boolean;
    hasEditorBeenCanceledAndClosed(): boolean;
};

const throwUnexpectedCallError = (method_name: string): void => {
    throw new Error(`Did not expect CloseSectionEditor::${method_name} to be called.`);
};

export const SectionEditorCloserStub = {
    withExpectedCall: (): CloseSectionEditorStub => {
        let has_editor_been_closed = false;
        let has_editor_been_canceled_and_closed = false;

        return {
            hasEditorBeenCanceledAndClosed: () => has_editor_been_canceled_and_closed,
            hasEditorBeenClosed: (): boolean => has_editor_been_closed,
            closeAndCancelEditor(): void {
                has_editor_been_canceled_and_closed = true;
            },
            closeEditor(): void {
                has_editor_been_closed = true;
            },
        };
    },
    withNoExpectedCall: (): CloseSectionEditor => ({
        closeEditor: (): void => throwUnexpectedCallError("closeEditor"),
        closeAndCancelEditor: (): void => throwUnexpectedCallError("closeAndCancelEditor"),
    }),
};
