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

declare module "@tuleap/plugin-tracker-artifact-ckeditor-image-upload" {
    interface HelpBlock {
        onFormatChange(new_format: string): void;
    }

    export class UploadImageFormFactory {
        constructor(doc: Document, locale: string);

        initiateImageUpload(
            ckeditor_instance: CKEDITOR.editor,
            textarea: HTMLTextAreaElement,
        ): void;

        forbidImageUpload(ckeditor_instance: CKEDITOR.editor): void;

        createHelpBlock(textarea: HTMLTextAreaElement): HelpBlock | null;
    }

    interface CKEditorUploadImageOptions {
        extraPlugins: "uploadimage";
        uploadUrl: string;
    }

    type EmptyObject = Record<string, never>;
    type PossibleCKEditorOptions = EmptyObject | CKEditorUploadImageOptions;

    export function getUploadImageOptions(element: HTMLTextAreaElement): PossibleCKEditorOptions;
}
