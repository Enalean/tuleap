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

import { ArtifactIdField } from "./ArtifactIdField";
import { ARTIFACT_ID_FIELD, ARTIFACT_ID_IN_TRACKER_FIELD } from "@tuleap/plugin-tracker-constants";

import type { HostElement } from "./ArtifactIdField";

const field_label = "Artifact Id",
    artifact_id_in_tracker = 60,
    current_artifact_id = 123;

function getHost(type: string): HostElement {
    return {
        field: {
            label: field_label,
            value: artifact_id_in_tracker,
            type,
        },
        currentArtifactIdentifier: { id: current_artifact_id },
    } as unknown as HostElement;
}

describe("ArtifactIdField", () => {
    it.each([
        [ARTIFACT_ID_FIELD, `#${current_artifact_id}`],
        [ARTIFACT_ID_IN_TRACKER_FIELD, String(artifact_id_in_tracker)],
    ])(
        'When the field type is "%s", Then it displays the field and its value will be "%s"',
        (field_type: string, expected_format: string) => {
            const host = getHost(field_type);
            const target = document.implementation
                .createHTMLDocument()
                .createElement("div") as unknown as ShadowRoot;

            const update = ArtifactIdField.content(host);

            update(host, target);

            const label = target.querySelector("[data-test=artifact-id-field-label]");
            const link = target.querySelector("[data-test=artifact-id-field-link]");

            if (!(label instanceof HTMLElement) || !(link instanceof HTMLAnchorElement)) {
                throw new Error("An element is missing in ArtifactIdField");
            }

            expect(label.textContent?.trim()).toBe(field_label);
            expect(link.textContent?.trim()).toBe(expected_format);
            expect(link.href).toBe(`/plugins/tracker/?aid=${current_artifact_id}`);
        },
    );
});
