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
import type { EditorState } from "prosemirror-state";
import { Plugin } from "prosemirror-state";
import type { PluginUploadOptions } from "./upload-file";
import { fileUploadHandler } from "./upload-file";
import type { GetText } from "@tuleap/gettext";
import { insertPoint } from "prosemirror-transform";
import type { FileUploader, FileIdentifier } from "@tuleap/file-upload";
import { getFileUploader } from "@tuleap/file-upload";

function insertFile(view: EditorView, insert_point: number, url: string): void {
    const { state, dispatch } = view;
    const node = state.schema.nodes.image.create({
        src: url,
    });

    const transaction = state.tr.insert(insert_point, node);
    dispatch(transaction);
}

function replaceSelectionWithFile(view: EditorView, url: string): void {
    const { state, dispatch } = view;
    const node = state.schema.nodes.image.create({
        src: url,
    });

    const transaction = state.tr.replaceSelectionWith(node);
    dispatch(transaction);
}

function handleEvent(
    files: FileList,
    options: PluginUploadOptions,
    gettext_provider: GetText,
    uploader: FileUploader,
    append_image_callback: (url: string) => void,
): void {
    const success_callback_with_insert_file = (
        id: FileIdentifier,
        download_href: string,
        file_name: string,
    ): void => {
        append_image_callback(download_href);
        options.onSuccessCallback(id, download_href, file_name);
    };
    fileUploadHandler(
        { ...options, onSuccessCallback: success_callback_with_insert_file },
        gettext_provider,
        uploader,
    )(files);
}

// instanceof does not work and makes the cypress test fail
const isDragEventWithData = (event: Event): event is DragEvent =>
    "dataTransfer" in event && event.dataTransfer !== null;

// instanceof does not work and makes the cypress test fail
const isClipboardEventWithData = (event: Event): event is ClipboardEvent =>
    "clipboardData" in event && event.clipboardData !== null;

const getFilesFromEvent = (event: DragEvent | ClipboardEvent): FileList | null => {
    if (isDragEventWithData(event)) {
        return event.dataTransfer?.files ?? null;
    }

    if (isClipboardEventWithData(event)) {
        return event.clipboardData?.files ?? null;
    }

    return null;
};

/**
 * Check that provided position points to a node accepting image nodes
 */
const isPositionValid = (state: EditorState, image_position: number): boolean => {
    const insertion_point = insertPoint(state.doc, image_position, state.schema.nodes.image);

    return insertion_point !== null;
};

export class PluginDropFile extends Plugin {
    uploader: FileUploader;

    constructor(options: PluginUploadOptions, gettext_provider: GetText) {
        super({
            props: {
                handleDOMEvents: {
                    drop: (view, event) => {
                        const files = getFilesFromEvent(event);
                        const drop_position = view.posAtCoords({
                            left: event.clientX,
                            top: event.clientY,
                        });

                        if (
                            !drop_position ||
                            !files ||
                            !isPositionValid(view.state, drop_position.pos)
                        ) {
                            event.preventDefault();
                            return true;
                        }

                        handleEvent(
                            files,
                            options,
                            gettext_provider,
                            this.uploader,
                            (url: string) => insertFile(view, drop_position.pos, url),
                        );
                        event.preventDefault();

                        return true;
                    },
                    paste: (view, event): boolean => {
                        const files = getFilesFromEvent(event);
                        if (
                            !files ||
                            files.length === 0 ||
                            !isPositionValid(view.state, view.state.selection.$from.pos)
                        ) {
                            return false;
                        }

                        handleEvent(
                            files,
                            options,
                            gettext_provider,
                            this.uploader,
                            (url: string) => replaceSelectionWithFile(view, url),
                        );
                        event.preventDefault();

                        return true;
                    },
                },
            },
        });
        this.uploader = getFileUploader();
    }

    async destroy(): Promise<void> {
        await this.cancelOngoingUpload();
    }

    public async cancelOngoingUpload(): Promise<void> {
        await this.uploader.cancelOngoingUpload();
    }
}

export function initPluginDropFile(
    options: PluginUploadOptions,
    gettext_provider: GetText,
): PluginDropFile {
    return new PluginDropFile(options, gettext_provider);
}
