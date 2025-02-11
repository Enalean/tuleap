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

import type { LinkedArtifact } from "../../domain/links/LinkedArtifact";

export interface LinkedArtifactPresenter extends LinkedArtifact {
    readonly is_parent: boolean;
    readonly is_marked_for_removal: boolean;
}

export const LinkedArtifactPresenter = {
    fromLinkedArtifact: (
        artifact: LinkedArtifact,
        is_parent: boolean,
        is_marked_for_removal: boolean,
    ): LinkedArtifactPresenter => ({
        ...artifact,
        is_parent,
        is_marked_for_removal,
    }),
};
