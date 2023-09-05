/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import {
    getAddFileButtonLabel,
    getEmptyLabel,
    getFileSizeText,
    getFileSubmittedByText,
    getMarkForRemovalLabel,
    getUndoFileRemovalLabel,
} from "../../../../gettext-catalog";
import type { AttachedFileDescription } from "../../../../domain/fields/file-field/AttachedFileDescription";
import type { FileFieldType } from "../../../../domain/fields/file-field/FileFieldType";
import "./NewFileToAttachElement";
import type {
    AttachedFileCollection,
    FileFieldControllerType,
    NewFileToAttachCollection,
} from "../../../../domain/fields/file-field/FileFieldController";

export const getActionButton = (
    file: AttachedFileDescription,
    is_disabled: boolean,
): UpdateFunction<FileField> => {
    if (!file.marked_for_removal) {
        const markForRemoval = (host: HostElement): void => {
            dispatch(host, "change", { bubbles: true });
            host.attached_files = host.controller.markFileForRemoval(file);
        };
        return html`
            <button
                type="button"
                class="tuleap-artifact-modal-field-file-preview-toggle tlp-button-danger tlp-button-outline tlp-button-small"
                disabled="${is_disabled}"
                onclick="${markForRemoval}"
                data-test="mark-for-removal"
            >
                <i class="far fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                ${getMarkForRemovalLabel()}
            </button>
        `;
    }

    const cancelRemoval = (host: HostElement): void => {
        dispatch(host, "change", { bubbles: true });
        host.attached_files = host.controller.cancelFileRemoval(file);
    };
    return html`
        <button
            type="button"
            class="tuleap-artifact-modal-field-file-preview-toggle tlp-button-success tlp-button-outline tlp-button-small"
            disabled="${is_disabled}"
            onclick="${cancelRemoval}"
            data-test="cancel-removal"
        >
            <i class="fas fa-undo tlp-button-icon" aria-hidden="true"></i>
            ${getUndoFileRemovalLabel()}
        </button>
    `;
};

export const getAttachedFileTemplate = (
    file: AttachedFileDescription,
    is_disabled: boolean,
): UpdateFunction<FileField> => {
    const removal_classes = file.marked_for_removal
        ? ["tuleap-artifact-modal-field-file", "marked-for-removal"]
        : ["tuleap-artifact-modal-field-file"];

    const style = file.display_as_image
        ? { "background-image": `url(${file.html_preview_url})` }
        : {};

    const submitter_text = sprintf(getFileSubmittedByText(), file.submitted_by);
    const size_text = sprintf(getFileSizeText(), prettyKibibytes(file.size));

    return html`
        <div class="${removal_classes}" data-test="attached-file">
            <div
                class="tuleap-artifact-modal-field-file-preview"
                style="${style}"
                data-test="attached-file-preview"
            >
                <a
                    href="${file.html_url}"
                    class="tuleap-artifact-modal-field-file-preview-link"
                    target="_blank"
                    rel="noreferrer"
                    data-test="attached-file-preview-link"
                >
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                </a>
            </div>
            <div class="tuleap-artifact-modal-field-file-details">
                <a
                    href="${file.html_url}"
                    target="_blank"
                    rel="noreferrer"
                    data-test="attached-file-link"
                >
                    ${file.name}
                </a>
                <blockquote class="tuleap-artifact-modal-field-file-details-info">
                    <p class="tlp-text" data-test="attached-file-description">
                        ${file.description}
                    </p>
                    <p class="tlp-text-muted">${submitter_text}</p>
                    <p class="tlp-text-muted">${size_text}</p>
                </blockquote>
            </div>
            ${getActionButton(file, is_disabled)}
        </div>
    `;
};

const getAttachedFilesTemplate = (host: FileField): UpdateFunction<FileField> => {
    if (!host.attached_files) {
        return html` <span class="tlp-text-muted">${getEmptyLabel()}</span> `;
    }
    return html`
        ${host.attached_files.map((file) => getAttachedFileTemplate(file, host.disabled))}
    `;
};

const onClickAddNewFileToAttach = (host: HostElement): void => {
    host.new_files = host.controller.addNewFileToAttach();
};

export const isRequired = (host: HostElement): boolean => {
    if (host.attached_files) {
        const all_marked_as_removed = host.attached_files.every(
            (attached_file: AttachedFileDescription) => attached_file.marked_for_removal,
        );
        if (!all_marked_as_removed) {
            return false;
        }
    }
    return host.field.required;
};

export const getAddNewFileToAttachButtonTemplate = (
    host: FileField,
): UpdateFunction<FileField> => html`
    <button
        type="button"
        class="tlp-button-primary tlp-button-outline tlp-button-small"
        onclick="${onClickAddNewFileToAttach}"
        disabled="${host.disabled}"
        data-test="add-new-file"
    >
        <i class="fas fa-plus tlp-button-icon" aria-hidden="true"></i>
        ${getAddFileButtonLabel()}
    </button>
`;

export interface FileField {
    readonly field: FileFieldType;
    readonly controller: FileFieldControllerType;
    new_files: NewFileToAttachCollection;
    attached_files: AttachedFileCollection;
    readonly disabled: boolean;
    content(): HTMLElement;
}
export type HostElement = FileField & HTMLElement;

export const FileField = define<FileField>({
    tag: "tuleap-artifact-modal-file-field",
    field: undefined,
    controller: {
        set(host: FileField, controller: FileFieldControllerType) {
            host.new_files = controller.getNewFilesToAttach();
            host.attached_files = controller.getAttachedFiles();
            return controller;
        },
    },
    new_files: undefined,
    attached_files: undefined,
    disabled: false,
    content: (host) => html`
        <div class="tlp-form-element">
            <label class="tlp-label" data-test="file-field-label">
                ${host.field.label}${host.field.required &&
                html`<i class="fas fa-asterisk" aria-hidden="true"></i>`}
            </label>
            ${getAttachedFilesTemplate(host)}
            ${host.new_files.map((file) => {
                const onFileChanged = (host: HostElement, event: CustomEvent): void => {
                    host.new_files = host.controller.setFileOfNewFileToAttach(
                        file,
                        event.detail.file,
                    );
                };
                const onDescriptionChanged = (host: HostElement, event: CustomEvent): void => {
                    host.new_files = host.controller.setDescriptionOfNewFileToAttach(
                        file,
                        event.detail.description,
                    );
                };
                const onReset = (host: HostElement): void => {
                    host.new_files = host.controller.reset(file);
                };
                return html`
                    <tuleap-artifact-modal-new-file-attach
                        disabled="${host.disabled}"
                        required="${isRequired(host)}"
                        description="${file.description}"
                        onfile-changed="${onFileChanged}"
                        ondescription-changed="${onDescriptionChanged}"
                        onreset="${onReset}"
                        data-test="new-file-to-attach"
                    ></tuleap-artifact-modal-new-file-attach>
                `;
            })}
            ${getAddNewFileToAttachButtonTemplate(host)}
        </div>
    `,
});
