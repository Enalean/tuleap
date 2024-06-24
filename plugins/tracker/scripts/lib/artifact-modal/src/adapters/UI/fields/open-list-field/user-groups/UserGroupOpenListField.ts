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

import { define } from "hybrids";
import { renderUserGroupOpenListField } from "./UserGroupOpenListFieldTemplate";
import type { UserGroupOpenListFieldPresenter } from "./UserGroupOpenListFieldPresenter";
import type { ControlUserGroupOpenListField } from "./UserGroupOpenListFieldController";

export const TAG = "tuleap-artifact-modal-user-group-open-list-field";

export type UserGroupOpenListField = {
    disabled: boolean;
    controller: ControlUserGroupOpenListField;
};

export type InternalUserGroupOpenListField = Readonly<UserGroupOpenListField> & {
    render(): HTMLElement;
    select_element: HTMLSelectElement;
    presenter: UserGroupOpenListFieldPresenter;
};

export type HostElement = InternalUserGroupOpenListField & HTMLElement;

export const UserGroupOpenListField = define<InternalUserGroupOpenListField>({
    tag: TAG,
    presenter: (host, presenter) => presenter ?? host.controller.buildInitialPresenter(),
    disabled: false,
    select_element: (host: InternalUserGroupOpenListField) => {
        const select = host.render().querySelector("[data-role=select-element]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error(`Unable to find the <select> in the UserGroupOpenListField`);
        }

        return select;
    },
    controller: {
        value: (host, controller) => controller,
        connect: (host) => {
            host.controller.initSelect2(host);
        },
    },
    render: renderUserGroupOpenListField,
});
