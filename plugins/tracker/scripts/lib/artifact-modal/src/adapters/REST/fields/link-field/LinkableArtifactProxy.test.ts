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

import type { ArtifactWithStatus } from "../../ArtifactWithStatus";
import type { ColorName } from "@tuleap/plugin-tracker-constants";
import { ARTIFACT_TYPE } from "@tuleap/plugin-tracker-constants";
import type { UserHistoryEntry } from "@tuleap/core-rest-api-types";
import { LinkableArtifactProxy } from "./LinkableArtifactProxy";

const ARTIFACT_ID = 801;
const TITLE = "chigger";
const CROSS_REFERENCE = `bugs #${ARTIFACT_ID}`;
const COLOR: ColorName = "flamingo-pink";
const STATUS = "Review";
const COLOR_STATUS = "daphne-blue";
const HTML_URI = "/plugins/tracker/?aid=" + ARTIFACT_ID;
const PROJECT_ID = 115;
const PROJECT_LABEL = "🐹 Guinea Pig";

describe(`LinkableArtifactProxy`, () => {
    it(`builds a LinkableArtifact from an Artifact representation from the API`, () => {
        const api_artifact: ArtifactWithStatus = {
            id: ARTIFACT_ID,
            title: TITLE,
            xref: CROSS_REFERENCE,
            tracker: {
                color_name: COLOR,
                project: { id: PROJECT_ID, label: PROJECT_LABEL, icon: "" },
            },
            full_status: { value: STATUS, color: COLOR_STATUS },
            is_open: false,
            html_url: HTML_URI,
        };

        const artifact = LinkableArtifactProxy.fromAPIArtifact(api_artifact);

        expect(artifact.id).toBe(ARTIFACT_ID);
        expect(artifact.title).toBe(TITLE);
        expect(artifact.xref.ref).toBe(CROSS_REFERENCE);
        expect(artifact.xref.color).toBe(COLOR);
        expect(artifact.status?.value).toBe(STATUS);
        expect(artifact.status?.color).toBe(COLOR_STATUS);
        expect(artifact.is_open).toBe(false);
        expect(artifact.uri).toBe(HTML_URI);
        expect(artifact.project.id).toBe(PROJECT_ID);
        expect(artifact.project.label).toBe(PROJECT_LABEL);
    });

    it(`builds a LinkableArtifact from a History entry representation from the API`, () => {
        const api_entry: UserHistoryEntry = {
            type: ARTIFACT_TYPE,
            per_type_id: ARTIFACT_ID,
            title: TITLE,
            project: { id: PROJECT_ID, label: PROJECT_LABEL, icon: "" },
            xref: CROSS_REFERENCE,
            color_name: COLOR,
            html_url: HTML_URI,
            icon_name: "",
            badges: [{ label: STATUS, color: null }],
            quick_links: [],
        };

        const artifact = LinkableArtifactProxy.fromAPIUserHistory(api_entry);

        expect(artifact.id).toBe(ARTIFACT_ID);
        expect(artifact.title).toBe(TITLE);
        expect(artifact.xref.ref).toBe(CROSS_REFERENCE);
        expect(artifact.xref.color).toBe(COLOR);
        expect(artifact.status).toStrictEqual({ value: STATUS, color: null });
        expect(artifact.is_open).toBe(true);
        expect(artifact.uri).toBe(HTML_URI);
        expect(artifact.project.id).toBe(PROJECT_ID);
        expect(artifact.project.label).toBe(PROJECT_LABEL);
    });
});
