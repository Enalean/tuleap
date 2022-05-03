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

import type { LinkedArtifact } from "../../src/domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactIdentifierStub } from "./LinkedArtifactIdentifierStub";
import { ArtifactCrossReferenceStub } from "./ArtifactCrossReferenceStub";
import type { LinkType } from "../../src/domain/fields/link-field-v2/LinkType";
import { LinkTypeStub } from "./LinkTypeStub";

export const LinkedArtifactStub = {
    withDefaults: (data?: Partial<LinkedArtifact>): LinkedArtifact => ({
        identifier: LinkedArtifactIdentifierStub.withId(8),
        title: "precool",
        status: "Todo",
        is_open: true,
        uri: "/plugins/tracker/?aid=8",
        xref: ArtifactCrossReferenceStub.withRefAndColor("tasks #8", "clockwork-orange"),
        link_type: LinkTypeStub.buildChildLinkType(),
        ...data,
    }),

    withIdAndType: (id: number, link_type: LinkType): LinkedArtifact => ({
        identifier: LinkedArtifactIdentifierStub.withId(id),
        title: "precool",
        status: "Todo",
        is_open: true,
        uri: `/plugins/tracker/?aid=${id}`,
        xref: ArtifactCrossReferenceStub.withRefAndColor(`tasks #${id}`, "clockwork-orange"),
        link_type,
    }),
};
