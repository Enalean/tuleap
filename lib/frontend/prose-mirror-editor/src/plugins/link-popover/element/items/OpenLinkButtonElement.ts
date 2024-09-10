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

export const TAG = "open-link-button";

export type OpenLinkButton = TypedLinkPopoverButton & {
    type: "open-link";
    sanitized_link_href: string;
};

export type OpenLinkButtonElement = Omit<OpenLinkButton, "type"> & {
    gettext_provider: GetText;
};

export type InternalOpenLinkButtonElement = Readonly<OpenLinkButtonElement> & {
    render(): HTMLElement;
};

export type HostElement = InternalOpenLinkButtonElement & HTMLElement;

export const renderOpenLinkButton = (
    host: InternalOpenLinkButtonElement,
    gettext_provider: GetText,
): UpdateFunction<InternalOpenLinkButtonElement> => html`
    <div class="tlp-button-bar-item">
        <a
            class="tlp-button-outline tlp-button-secondary tlp-button-small"
            title="${gettext_provider.gettext("Open link")}"
            href="${host.sanitized_link_href}"
            target="_blank"
            data-test="open-link-button"
        >
            <i class="fa-solid fa-external-link-alt" role="img"></i>
        </a>
    </div>
`;

export const isOpenLinkButtonElement = (
    element: HTMLElement,
): element is HTMLElement & OpenLinkButtonElement => element.tagName === TAG.toUpperCase();

export const createOpenLinkButton = (
    doc: Document,
    gettext_provider: GetText,
    props: OpenLinkButton,
): HTMLElement => {
    const button = doc.createElement(TAG);
    if (!isOpenLinkButtonElement(button)) {
        throw new Error("Unable to create an open link button");
    }
    button.sanitized_link_href = props.sanitized_link_href;
    button.gettext_provider = gettext_provider;

    return button;
};

define<InternalOpenLinkButtonElement>({
    tag: TAG,
    gettext_provider: (host, gettext_provider) => gettext_provider,
    sanitized_link_href: "",
    render: (host) => renderOpenLinkButton(host, host.gettext_provider),
});
