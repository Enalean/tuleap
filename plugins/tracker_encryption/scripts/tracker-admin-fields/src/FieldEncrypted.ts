/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
import { gettext_provider } from "./gettext-provider";

export const TAG = "tuleap-field-encrypted";

export type FieldEncrypted = {
    field_id: number;
};

export type InternalFieldEncrypted = Readonly<FieldEncrypted> & {
    field_type: "text" | "password";
    icon_name: "fa-eye" | "fa-eye-slash";
};

export type HostElement = InternalFieldEncrypted & HTMLElement;

const renderFieldEncrypted = (
    host: InternalFieldEncrypted,
): UpdateFunction<InternalFieldEncrypted> => {
    const toggleSecretValue = (host: InternalFieldEncrypted): void => {
        if (host.field_type === "password") {
            host.field_type = "text";
            host.icon_name = "fa-eye";
            return;
        }

        host.field_type = "password";
        host.icon_name = "fa-eye-slash";
    };

    return html`
        <div class="tlp-form-element tlp-form-element-append">
            <input
                type="${host.field_type}"
                class="tlp-input"
                id="field-${host.field_id}"
                name="icon"
                value="${gettext_provider.gettext("Secret value")}"
                readonly
            />
            <button
                type="button"
                class="tlp-append tlp-button-secondary tlp-button-outline tlp-tooltip tlp-tooltip-top"
                data-tlp-tooltip="${gettext_provider.gettext("Click to reveal secret value")}"
                aria-label="${gettext_provider.gettext("Click to reveal secret value")}"
                data-input-id="field-${host.field_id}"
                onclick="${(host: InternalFieldEncrypted): void => toggleSecretValue(host)}"
            >
                <i class="fa-fw fa-solid ${host.icon_name}" aria-hidden="true"></i>
            </button>
        </div>
    `;
};

define<InternalFieldEncrypted>({
    tag: TAG,
    field_id: 0,
    field_type: "password",
    icon_name: "fa-eye-slash",
    render: renderFieldEncrypted,
});
