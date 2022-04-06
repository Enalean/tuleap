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

import type { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";

export interface LinkFieldPresenter {
    readonly linked_artifacts: LinkedArtifactPresenter[];
    readonly has_loaded_content: boolean;
    readonly is_loading: boolean;
}

export const LinkFieldPresenter = {
    buildLoadingState: (): LinkFieldPresenter => ({
        linked_artifacts: [],
        is_loading: true,
        has_loaded_content: false,
    }),

    fromArtifacts: (artifact_presenters: LinkedArtifactPresenter[]): LinkFieldPresenter => ({
        linked_artifacts: artifact_presenters,
        is_loading: false,
        has_loaded_content: true,
    }),

    forFault: (): LinkFieldPresenter => ({
        linked_artifacts: [],
        is_loading: false,
        has_loaded_content: true,
    }),
};
