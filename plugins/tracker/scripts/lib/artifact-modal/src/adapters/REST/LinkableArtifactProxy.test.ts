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

import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import type { TrackerColorName } from "@tuleap/plugin-tracker-constants/src/constants";
import { LinkableArtifactProxy } from "./LinkableArtifactProxy";

const ARTIFACT_ID = 801;
const TITLE = "chigger";
const CROSS_REFERENCE = `bugs #${ARTIFACT_ID}`;
const COLOR: TrackerColorName = "flamingo-pink";
const STATUS = "Review";
const HTML_URI = "/plugins/tracker/?aid=" + ARTIFACT_ID;

describe(`LinkableArtifactProxy`, () => {
    it(`builds a LinkableArtifact from an Artifact representation from the API`, () => {
        const api_artifact: ArtifactWithStatus = {
            id: ARTIFACT_ID,
            title: TITLE,
            xref: CROSS_REFERENCE,
            tracker: { color_name: COLOR },
            status: STATUS,
            is_open: false,
            html_url: HTML_URI,
        };

        const artifact = LinkableArtifactProxy.fromAPIArtifact(api_artifact);

        expect(artifact.id).toBe(ARTIFACT_ID);
        expect(artifact.title).toBe(TITLE);
        expect(artifact.xref.ref).toBe(CROSS_REFERENCE);
        expect(artifact.xref.color).toBe(COLOR);
        expect(artifact.status).toBe(STATUS);
        expect(artifact.is_open).toBe(false);
        expect(artifact.uri).toBe(HTML_URI);
    });
});
