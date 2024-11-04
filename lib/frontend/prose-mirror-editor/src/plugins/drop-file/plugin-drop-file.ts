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
import { insertPoint } from "prosemirror-transform";
import { fileUploadHandler } from "./upload-file";
import type { FileUploadOptions } from "./types";
import type { Option } from "@tuleap/option";
import type { GetText } from "@tuleap/gettext";
import type { Upload } from "tus-js-client";

function insertFile(view: EditorView, insert_point: number, url: string): void {
    const { state, dispatch } = view;
    const node = state.schema.nodes.image.create({
        src: url,
    });

    const transaction = state.tr.insert(insert_point, node);
    dispatch(transaction);
}

export interface OngoingUpload {
    readonly cancel: () => void;
}

function handleDrop(
    view: EditorView,
    event: DragEvent,
    options: FileUploadOptions,
    gettext_provider: GetText,
    uploaders: Array<Upload>,
    drop_point: number,
): Promise<Option<ReadonlyArray<OngoingUpload>>> {
    const success_callback_with_insert_file = (
        id: number,
        download_href: string,
        file_name: string,
    ): void => {
        insertFile(view, drop_point, download_href);
        options.onSuccessCallback(id, download_href, file_name);
    };
    return fileUploadHandler(
        { ...options, onSuccessCallback: success_callback_with_insert_file },
        gettext_provider,
        uploaders,
    )(event);
}

export class PluginDropFile extends Plugin {
    uploaders: Array<Upload>;

    constructor(options: FileUploadOptions, gettext_provider: GetText) {
        super({
            props: {
                handleDOMEvents: {
                    drop: (view, event) => {
                        if (!(event.target instanceof Node)) {
                            event.preventDefault();
                            return true;
                        }

                        const drop_point = insertPoint(
                            view.state.doc,
                            view.posAtDOM(event.target, 0),
                            view.state.schema.nodes.image,
                        );

                        if (!drop_point) {
                            event.preventDefault();
                            return true;
                        }

                        handleDrop(
                            view,
                            event,
                            options,
                            gettext_provider,
                            this.uploaders,
                            drop_point,
                        );

                        return true;
                    },
                },
            },
        });
        this.uploaders = [];
    }

    async destroy(): Promise<void> {
        await this.cancelOngoingUpload();
    }

    public async cancelOngoingUpload(): Promise<void> {
        for (let i = 0; i < this.uploaders.length; i++) {
            await this.uploaders[i].abort(true);
        }

        this.uploaders = [];
    }
}

export function initPluginDropFile(
    options: FileUploadOptions,
    gettext_provider: GetText,
): PluginDropFile {
    return new PluginDropFile(options, gettext_provider);
}
