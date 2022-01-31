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
import type { FormatReadonlyDateField } from "../../../../domain/fields/readonly-date-field/FormatReadonlyDateField";

export type HostElement = DateReadonlyField & HTMLElement;

interface FieldDateReadonlyType {
    readonly label: string;
    readonly value: string;
}

export interface DateReadonlyField {
    readonly field: FieldDateReadonlyType;
    readonly formatter: FormatReadonlyDateField;
    readonly content: () => HTMLElement;
}

const getFormattedDate = (host: DateReadonlyField): string =>
    host.formatter.format(host.field.value);

export const DateReadonlyField = define<DateReadonlyField>({
    tag: "tuleap-artifact-modal-date-readonly-field",
    field: undefined,
    formatter: undefined,
    content: (host) => {
        return html`
            <div class="tlp-property">
                <label class="tlp-label" data-test="date-readonly-field-label">
                    ${host.field.label}
                </label>
                <span data-test="date-readonly-field-date">${getFormattedDate(host)}</span>
            </div>
        `;
    },
});
