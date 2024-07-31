/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { EditorView } from "prosemirror-view";
import { Plugin } from "prosemirror-state";
import { custom_schema } from "../../custom_schema";
import { fileUploadHandler } from "./upload-file";
import type { FileUploadOptions } from "./types";

function insertFile(view: EditorView, url: string): void {
    const { state, dispatch } = view;
    const node = custom_schema.nodes.image.create({
        src: url,
    });
    const transaction = state.tr.replaceSelectionWith(node);
    dispatch(transaction);
}

function handleDrop(view: EditorView, event: DragEvent, options: FileUploadOptions): void {
    const success_callback_with_insert_file = (id: number, download_href: string): void => {
        insertFile(view, download_href);
        options.onSuccessCallback(id, download_href);
    };
    fileUploadHandler({ ...options, onSuccessCallback: success_callback_with_insert_file })(event);
}

export function initPluginDropFile(options: FileUploadOptions): Plugin {
    return new Plugin({
        props: {
            handleDOMEvents: {
                drop: (view: EditorView, event: DragEvent): boolean => {
                    handleDrop(view, event, options);
                    return true;
                },
            },
        },
    });
}
