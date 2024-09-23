/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import "@tuleap/copy-to-clipboard";

export const TAG = "copy-to-clipboard-button";

export type CopyToClipboardButtonElement = {
    value_to_copy: string;
    value_copied_title: string;
    copy_value_title: string;
};

export type InternalCopyToClipboardButtonElement = Readonly<CopyToClipboardButtonElement> & {
    has_been_copied_to_clipboard: boolean;
    render(): HTMLElement;
};

export type HostElement = InternalCopyToClipboardButtonElement & HTMLElement;

const onCopiedToClipboard = (host: InternalCopyToClipboardButtonElement): void => {
    host.has_been_copied_to_clipboard = true;

    setTimeout(() => {
        host.has_been_copied_to_clipboard = false;
    }, 2000);
};

const getIcon = (
    host: InternalCopyToClipboardButtonElement,
): UpdateFunction<InternalCopyToClipboardButtonElement> => {
    const icon_classnames = {
        "fa-regular fa-copy": !host.has_been_copied_to_clipboard,
        "fa-solid fa-check": host.has_been_copied_to_clipboard,
    };

    return html`<i class="${icon_classnames}" role="img" data-test="copy-to-clipboard-icon"></i>`;
};

export const renderCopyToClipboardItem = (
    host: InternalCopyToClipboardButtonElement,
): UpdateFunction<InternalCopyToClipboardButtonElement> => html`
    <div class="tlp-button-bar-item">
        <copy-to-clipboard
            class="tlp-button-outline tlp-button-secondary tlp-button-small"
            title="${host.has_been_copied_to_clipboard
                ? host.value_copied_title
                : host.copy_value_title}"
            value="${host.value_to_copy}"
            data-test="copy-link-button"
            oncopied-to-clipboard="${onCopiedToClipboard}"
        >
            ${getIcon(host)}
        </copy-to-clipboard>
    </div>
`;

define<InternalCopyToClipboardButtonElement>({
    tag: TAG,
    has_been_copied_to_clipboard: false,
    value_to_copy: "",
    value_copied_title: "",
    copy_value_title: "",
    render: renderCopyToClipboardItem,
});
