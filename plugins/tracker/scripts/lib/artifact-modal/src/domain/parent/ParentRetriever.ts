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

import type { RetrieveParent } from "./RetrieveParent";
import type { ParentArtifactIdentifier } from "./ParentArtifactIdentifier";
import { Fault } from "@tuleap/fault";
import type { Artifact } from "../Artifact";
import type { RetrieveArtifact } from "../RetrieveArtifact";

export const ParentRetriever = (retriever: RetrieveArtifact): RetrieveParent => ({
    getParent: (parent_identifier: ParentArtifactIdentifier | null): Promise<Artifact | Fault> => {
        if (parent_identifier === null) {
            return Promise.resolve(Fault.fromMessage("Current artifact has no parent"));
        }
        return retriever.getArtifact(parent_identifier);
    },
});
