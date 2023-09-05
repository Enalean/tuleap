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

import { define, html } from "hybrids";
import { getEmptyCrossReferencesCollectionText } from "../../../../gettext-catalog";

export type HostElement = CrossReferencesField & HTMLElement;

export interface CrossReference {
    readonly url: string;
    readonly ref: string;
}

interface FieldCrossReferencesType {
    readonly label: string;
    readonly value: CrossReference[];
}

interface CrossReferencesField {
    readonly field: FieldCrossReferencesType;
    readonly content: () => HTMLElement;
}

export const CrossReferencesField = define<CrossReferencesField>({
    tag: "tuleap-artifact-modal-cross-references-field",
    field: undefined,
    content: (host) => html`
        <div class="tlp-property">
            <label class="tlp-label" data-test="cross-references-field-label">
                ${host.field.label}
            </label>
            ${host.field.value.length === 0 &&
            html`
                <p
                    class="tuleap-artifact-modal-field-empty-value"
                    data-test="cross-references-field-empty-state"
                >
                    ${getEmptyCrossReferencesCollectionText()}
                </p>
            `}
            ${host.field.value.length > 0 &&
            html`
                <ul>
                    ${host.field.value.map(
                        (value) => html`
                            <li>
                                <a
                                    href="${value.url}"
                                    data-test="cross-references-field-cross-reference-link"
                                    title=""
                                    class="cross-reference"
                                >
                                    ${value.ref}
                                </a>
                            </li>
                        `,
                    )}
                </ul>
            `}
        </div>
    `,
});
