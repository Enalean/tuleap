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

import type { ColorName } from "@tuleap/plugin-tracker-constants";
import type { LinkableArtifact } from "../../src/domain/fields/link-field/LinkableArtifact";
import { ArtifactCrossReferenceStub } from "./ArtifactCrossReferenceStub";

export const LinkableArtifactStub = {
    withDefaults: (data?: Partial<LinkableArtifact>): LinkableArtifact => ({
        id: 456,
        title: "flocculation",
        xref: ArtifactCrossReferenceStub.withRefAndColor("story #456", "lake-placid-blue"),
        uri: "/plugins/tracker/?aid=456",
        status: { value: "Ongoing", color: null },
        is_open: true,
        project: { id: 104, label: "🐹 Guinea Pig" },
        ...data,
    }),

    withCrossReference: (
        id: number,
        title: string,
        xref: string,
        color: ColorName,
    ): LinkableArtifact => ({
        id,
        title,
        xref: ArtifactCrossReferenceStub.withRefAndColor(xref, color),
        uri: "/plugins/tracker/?aid=" + id,
        status: { value: "Ongoing", color: null },
        is_open: true,
        project: { id: 147, label: "Project X" },
    }),
};
