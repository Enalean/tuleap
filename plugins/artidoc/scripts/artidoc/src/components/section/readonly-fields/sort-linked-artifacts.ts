/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { IS_CHILD_LINK_TYPE, REVERSE_DIRECTION } from "@tuleap/plugin-tracker-constants";
import type {
    LinkType,
    ReadonlyFieldLinkedArtifact,
} from "@/sections/readonly-fields/ReadonlyFields";

const isReverseChild = (link_type: LinkType): boolean =>
    link_type.shortname === IS_CHILD_LINK_TYPE && link_type.direction === REVERSE_DIRECTION;

export const sortLinkedArtifacts = (
    unsorted_artifacts: ReadonlyFieldLinkedArtifact[],
): ReadonlyFieldLinkedArtifact[] => {
    const parents = unsorted_artifacts.filter((artifact) => isReverseChild(artifact.link_type));
    const not_parents = unsorted_artifacts.filter(
        (artifact) => !isReverseChild(artifact.link_type),
    );
    return [...parents, ...not_parents];
};
