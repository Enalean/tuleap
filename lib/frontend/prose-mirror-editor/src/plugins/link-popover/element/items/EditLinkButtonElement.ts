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

import { define, html, dispatch } from "hybrids";
import type { UpdateFunction } from "hybrids";

export const TAG = "edit-link-button";

export type EditLinkButtonElement = {
    button_title: string;
};

type InternalEditLinkButtonElement = Readonly<EditLinkButtonElement>;

export type HostElement = InternalEditLinkButtonElement & HTMLElement;

export const renderEditLinkButton = (
    host: HostElement,
): UpdateFunction<InternalEditLinkButtonElement> => {
    const onClickDispatchEvent = (host: HostElement): void => {
        dispatch(host, "toggle-edition-mode");
    };

    return html`
        <div class="tlp-button-bar-item">
            <button
                class="tlp-button-outline tlp-button-secondary tlp-button-small"
                title="${host.button_title}"
                onclick="${onClickDispatchEvent}"
                data-test="edit-link-button"
            >
                <i class="fa-solid fa-pencil" role="img"></i>
            </button>
        </div>
    `;
};

define<InternalEditLinkButtonElement>({
    tag: TAG,
    button_title: "",
    render: renderEditLinkButton,
});
