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
import type { GetText } from "@tuleap/gettext";
import type { TypedLinkPopoverButton } from "../LinkPopoverElement";

export const TAG = "remove-link-button";

export type RemoveLinkCallback = () => void;

export type RemoveLinkButton = TypedLinkPopoverButton & {
    type: "remove-link";
    gettext_provider: GetText;
    remove_link_callback: RemoveLinkCallback;
};

export type RemoveLinkButtonElement = Omit<RemoveLinkButton, "type">;
export type InternalRemoveLnkButton = Readonly<RemoveLinkButtonElement>;

export type HostElement = InternalRemoveLnkButton & HTMLElement;

export const renderRemoveLinkButton = (
    host: HostElement,
    gettext_provider: GetText,
): UpdateFunction<InternalRemoveLnkButton> => html`
    <div class="tlp-button-bar-item">
        <button
            class="tlp-button-outline tlp-button-secondary tlp-button-small"
            title="${gettext_provider.gettext("Remove link")}"
            onclick="${host.remove_link_callback}"
            data-test="remove-link-button"
        >
            <i class="fa-solid fa-link-slash" role="img"></i>
        </button>
    </div>
`;

export const isRemoveLinkButtonElement = (
    element: HTMLElement,
): element is HTMLElement & RemoveLinkButtonElement => element.tagName === TAG.toUpperCase();

export const createRemoveLinkButton = (
    doc: Document,
    gettext_provider: GetText,
    props: RemoveLinkButton,
): HTMLElement => {
    const button = doc.createElement(TAG);
    if (!isRemoveLinkButtonElement(button)) {
        throw new Error("Unable to create a remove link button");
    }

    button.gettext_provider = gettext_provider;
    button.remove_link_callback = props.remove_link_callback;

    return button;
};

define<InternalRemoveLnkButton>({
    tag: TAG,
    gettext_provider: (host, gettext_provider) => gettext_provider,
    remove_link_callback: (host, remove_link_callback) => remove_link_callback,
    render: (host) => renderRemoveLinkButton(host, host.gettext_provider),
});
