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
import { ARTIFACT_ID_FIELD } from "../../../../../../../constants/fields-constants";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

import type {
    ArtifactIdFieldIdentifier,
    ArtifactIdInTrackerFieldIdentifier,
} from "../../../../../../../constants/fields-constants";

export type HostElement = ArtifactIdField & HTMLElement;

interface FieldArtifactIdType {
    readonly label: string;
    readonly value: number;
    readonly type: ArtifactIdFieldIdentifier | ArtifactIdInTrackerFieldIdentifier;
}

export interface ArtifactIdField {
    readonly field: FieldArtifactIdType;
    readonly currentArtifactIdentifier: CurrentArtifactIdentifier;
    readonly content: () => HTMLElement;
}

const getArtifactUrl = (host: ArtifactIdField): string =>
    `/plugins/tracker/?aid=${host.currentArtifactIdentifier.id}`;

const getFormattedValue = (host: ArtifactIdField): string => {
    const { id } = host.currentArtifactIdentifier;
    return host.field.type === ARTIFACT_ID_FIELD ? `#${id}` : String(host.field.value);
};

export const ArtifactIdField = define<ArtifactIdField>({
    tag: "tuleap-artifact-modal-artifact-id-field",
    field: undefined,
    currentArtifactIdentifier: undefined,
    content: (host) => html`
        <div class="tlp-property">
            <label class="tlp-label" data-test="artifact-id-field-label">${host.field.label}</label>
            <a href="${getArtifactUrl(host)}" data-test="artifact-id-field-link">
                ${getFormattedValue(host)}
            </a>
        </div>
    `,
});
