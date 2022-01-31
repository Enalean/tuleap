/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { html, define } from "hybrids";

export type HostElement = UserAvatarField & HTMLElement;

interface UserAvatarValue {
    readonly avatar_url: string;
    readonly user_url: string;
    readonly display_name: string;
}

export interface FieldUserAvatarType {
    readonly label: string;
    readonly value: UserAvatarValue;
}

export interface UserAvatarField {
    readonly field: FieldUserAvatarType;
    readonly content: () => HTMLElement;
}

export const UserAvatarField = define<UserAvatarField>({
    tag: "tuleap-artifact-modal-user-avatar-field",
    field: undefined,
    content: (host) => html`
        <div class="tlp-property">
            <label class="tlp-label" data-test="user-avatar-field-label">${host.field.label}</label>
            <div class="tuleap-artifact-modal-artifact-field-user">
                <div class="tlp-avatar">
                    ${host.field.value.avatar_url &&
                    html`
                        <img
                            src="${host.field.value.avatar_url}"
                            alt="avatar"
                            data-test="user-avatar-field-avatar-image"
                        />
                    `}
                </div>
                <a href="${host.field.value.user_url}" data-test="user-avatar-field-user-link">
                    ${host.field.value.display_name}
                </a>
            </div>
        </div>
    `,
});
