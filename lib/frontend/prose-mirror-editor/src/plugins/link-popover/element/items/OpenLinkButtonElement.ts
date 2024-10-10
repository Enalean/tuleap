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

export const TAG = "open-link-button";

export type OpenLinkButtonElement = {
    sanitized_link_href: string;
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

const open_link_button = define.compile<InternalOpenLinkButtonElement>({
    tag: TAG,
    gettext_provider: (host, gettext_provider) => gettext_provider,
    sanitized_link_href: "",
    render: (host) => renderOpenLinkButton(host, host.gettext_provider),
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, open_link_button);
}
