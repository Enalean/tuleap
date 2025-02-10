/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import { LinkedArtifactProxy } from "./LinkedArtifactProxy";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";

const ARTIFACT_ID = 7;
const TITLE = "maeandroid";
const STATUS = "Ongoing";
const HTML_URI = "/plugins/tracker/?aid=" + ARTIFACT_ID;
const TRACKER_SHORTNAME = "story";
const COLOR = "neon-green";
const STATUS_COLOR = "daphne-blue";
const CROSS_REFERENCE = `${TRACKER_SHORTNAME} #${ARTIFACT_ID}`;

describe(`LinkedArtifactProxy`, () => {
    it(`builds a Linked Artifact from an Artifact representation from the API`, () => {
        const api_artifact: ArtifactWithStatus = {
            id: ARTIFACT_ID,
            title: TITLE,
            full_status: { value: STATUS, color: STATUS_COLOR },
            is_open: true,
            html_url: HTML_URI,
            xref: CROSS_REFERENCE,
            tracker: {
                color_name: COLOR,
                project: { id: 174, label: "Guinea Pig", icon: "🐹" },
            },
        };
        const link_type = LinkTypeStub.buildParentLinkType();

        const linked_artifact = LinkedArtifactProxy.fromAPIArtifactAndType(api_artifact, link_type);

        expect(linked_artifact.identifier.id).toBe(ARTIFACT_ID);
        expect(linked_artifact.title).toBe(TITLE);
        expect(linked_artifact.status?.value).toBe(STATUS);
        expect(linked_artifact.status?.color).toBe(STATUS_COLOR);
        expect(linked_artifact.is_open).toBe(true);
        expect(linked_artifact.uri).toBe(HTML_URI);
        expect(linked_artifact.xref.ref).toBe(CROSS_REFERENCE);
        expect(linked_artifact.xref.color).toBe(COLOR);
        expect(linked_artifact.link_type).toStrictEqual(link_type);
    });
});
