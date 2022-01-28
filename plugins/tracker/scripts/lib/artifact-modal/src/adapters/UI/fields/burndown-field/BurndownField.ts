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
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

export type HostElement = BurndownField & HTMLElement;

interface FieldBurndownType {
    readonly field_id: number;
    readonly label: string;
}

export interface BurndownField {
    readonly field: FieldBurndownType;
    readonly currentArtifactIdentifier: CurrentArtifactIdentifier;
    readonly content: () => HTMLElement;
}

const getBurndownImageUrl = (host: HostElement): string =>
    `/plugins/tracker/?formElement=${host.field.field_id}&func=show_burndown&src_aid=${host.currentArtifactIdentifier.id}`;

export const BurndownField = define<BurndownField>({
    tag: "tuleap-artifact-modal-burndown-field",
    field: undefined,
    currentArtifactIdentifier: undefined,
    content: (host) => html`
        <div class="tlp-property">
            <label class="tlp-label" data-test="burndown-field-label">${host.field.label}</label>
            <img
                src="${getBurndownImageUrl(host)}"
                alt="${host.field.label}"
                class="tuleap-artifact-modal-artifact-field-burndown-image"
                data-test="burndown-field-image"
            />
        </div>
    `,
});
