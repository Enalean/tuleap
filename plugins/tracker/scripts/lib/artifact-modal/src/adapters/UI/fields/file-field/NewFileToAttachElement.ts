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

import { define, html, dispatch } from "hybrids";
import { getFileDescriptionPlaceholder, getResetLabel } from "../../../../gettext-catalog";

const onFileChange = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement) || event.target.files === null) {
        return;
    }
    const file = event.target.files[0];
    dispatch(host, "file-changed", { detail: { file } });
};

export const onDescriptionInput = (host: HostElement, event: Event): void => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    const description = event.target.value;
    dispatch(host, "description-changed", { detail: { description } });
};

export const onClick = (host: HostElement): void => {
    if (!host.file_input) {
        return;
    }
    host.file_input.value = "";
    dispatch(host, "reset");
};

export interface NewFileToAttachElement {
    readonly disabled: boolean;
    readonly required: boolean;
    readonly description: string;
    readonly file_input: HTMLInputElement | null;
    content(): HTMLElement;
}
export type HostElement = HTMLElement & NewFileToAttachElement;

export const NewFileToAttachElement = define<NewFileToAttachElement>({
    tag: "tuleap-artifact-modal-new-file-attach",
    disabled: false,
    required: false,
    description: "",
    file_input: {
        get: (host) => {
            const element = host.content();
            const input = element.querySelector("[data-file-input]");
            if (!(input instanceof HTMLInputElement)) {
                return null;
            }
            return input;
        },
    },
    content: (host) => html`
        <div class="tuleap-artifact-modal-field-file-new-file">
            <input
                type="file"
                onchange="${onFileChange}"
                disabled="${host.disabled}"
                required="${host.required}"
                data-test="file-field-file-input"
                data-file-input
            />
            <div class="tuleap-artifact-modal-field-file-new-file-description">
                <input
                    type="text"
                    class="tlp-input tlp-input-small"
                    disabled="${host.disabled}"
                    placeholder="${getFileDescriptionPlaceholder()}"
                    oninput="${onDescriptionInput}"
                    value="${host.description}"
                    data-test="file-field-description-input"
                />
                <button
                    type="button"
                    class="tlp-button-secondary tlp-button-outline tlp-button-small"
                    disabled="${host.disabled}"
                    onclick="${onClick}"
                    data-test="file-field-reset"
                >
                    <i class="far fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                    ${getResetLabel()}
                </button>
            </div>
        </div>
    `,
});
