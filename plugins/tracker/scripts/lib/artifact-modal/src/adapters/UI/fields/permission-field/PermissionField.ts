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

import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import type { PermissionFieldControllerType } from "./PermissionFieldController";
import type { PermissionFieldPresenter } from "./PermissionFieldPresenter";
import { getPermissionFieldLabel } from "../../../../gettext-catalog";

interface PermissionField {
    readonly controller: PermissionFieldControllerType;
    field_presenter: PermissionFieldPresenter;
}
type InternalPermissionField = PermissionField & {
    content(): HTMLElement;
};
export type HostElement = InternalPermissionField & HTMLElement;

const onPermissionCheckboxChange = (host: PermissionField, event: Event): void => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
        return;
    }

    host.field_presenter = host.controller.setIsFieldUsedByDefault(Boolean(target.checked));
};

const onSelectChange = (host: PermissionField, event: Event): void => {
    const target = event.target;
    if (!(target instanceof HTMLSelectElement)) {
        return;
    }

    const granted_groups = Array.from(target.selectedOptions).map((option) => option.value);
    host.field_presenter = host.controller.setGrantedGroups(granted_groups);
};

export const getIsUsedByDefaultCheckbox = (
    host: PermissionField
): UpdateFunction<PermissionField> => html`
    <input
        type="checkbox"
        id="perm_${host.field_presenter.field_id}"
        data-test="permission-field-checkbox"
        checked="${host.field_presenter.is_used}"
        disabled="${host.field_presenter.is_field_disabled}"
        onchange="${onPermissionCheckboxChange}"
    />
`;

const getGrantedGroupsSelectLabelWithCheckboxIfNeeded = (
    host: PermissionField
): UpdateFunction<PermissionField> => {
    if (host.field_presenter.is_field_required) {
        return html`
            <label for="tracker_field_${host.field_presenter.field_id}" class="tlp-label">
                ${getPermissionFieldLabel()}
            </label>
        `;
    }

    return html`
        <label for="perm_${host.field_presenter.field_id}" class="tlp-label tlp-checkbox">
            ${getIsUsedByDefaultCheckbox(host)} ${getPermissionFieldLabel()}
        </label>
    `;
};

export const getGrantedGroupsSelect = (
    host: PermissionField
): UpdateFunction<PermissionField> => html`
    <select
        id="tracker_field_${host.field_presenter.field_id}"
        data-test="permission-field-select"
        class="tlp-select"
        onchange="${onSelectChange}"
        disabled="${host.field_presenter.is_select_box_disabled}"
        required="${host.field_presenter.is_select_box_required}"
        multiple
    >
        ${host.field_presenter.user_groups.map(
            (group) => html`
                <option
                    value="${group.id}"
                    selected="${host.field_presenter.granted_groups.includes(group.id)}"
                >
                    ${group.label}
                </option>
            `
        )}
    </select>
`;

export const PermissionField = define<InternalPermissionField>({
    tag: "tuleap-artifact-modal-permission-field",
    controller: {
        set(host, controller: PermissionFieldControllerType) {
            host.field_presenter = controller.buildPresenter();
            return controller;
        },
    },
    field_presenter: undefined,
    content: (host) => html`
        <div class="tlp-form-element">
            <label for="tracker_field_${host.field_presenter.field_id}" class="tlp-label">
                ${host.field_presenter.label}
                ${host.field_presenter.is_field_required &&
                html`<i class="fas fa-asterisk" aria-hidden="true"></i>`}
            </label>

            ${getGrantedGroupsSelectLabelWithCheckboxIfNeeded(host)} ${getGrantedGroupsSelect(host)}
        </div>
    `,
});
