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

import { PossibleParentsGroup } from "./PossibleParentsGroup";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";
import type { VerifyIsAlreadyLinked } from "../../../../domain/fields/link-field-v2/VerifyIsAlreadyLinked";
import type { GroupCollection } from "@tuleap/link-selector/src";

function buildPossibleParentsByProjectMap(
    possible_parents: readonly LinkableArtifact[]
): Map<number, LinkableArtifact[]> {
    const by_project_map = new Map<number, LinkableArtifact[]>();
    possible_parents.forEach((parent) => {
        const project_id = parent.project.id;
        const parent_group = by_project_map.get(project_id);
        if (!parent_group) {
            by_project_map.set(project_id, [parent]);
            return;
        }
        parent_group.push(parent);
    });
    return by_project_map;
}

export const LinkFieldPossibleParentsGroupsByProjectBuilder = {
    buildGroupsSortedByProject: (
        link_verifier: VerifyIsAlreadyLinked,
        possible_parents: readonly LinkableArtifact[]
    ): GroupCollection => {
        const by_project_map = buildPossibleParentsByProjectMap(possible_parents);

        return Array.from(by_project_map.values()).map((possible_parents_in_project) => {
            return PossibleParentsGroup.fromPossibleParents(
                link_verifier,
                possible_parents_in_project
            );
        });
    },
};
