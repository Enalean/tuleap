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

import type { LinkedArtifact } from "../../../../domain/fields/link-field-v2/LinkedArtifact";

export interface LinkFieldPresenter {
    readonly linked_artifacts: LinkedArtifact[];
    readonly error_message: string;
    readonly has_loaded_content: boolean;
    readonly is_loading: boolean;
}

export const LinkFieldPresenter = {
    buildLoadingState: (): LinkFieldPresenter => ({
        linked_artifacts: [],
        error_message: "",
        is_loading: true,
        has_loaded_content: false,
    }),

    forCreationMode: (): LinkFieldPresenter => ({
        linked_artifacts: [],
        error_message: "",
        is_loading: false,
        has_loaded_content: true,
    }),

    fromArtifacts: (artifacts: LinkedArtifact[]): LinkFieldPresenter => ({
        linked_artifacts: artifacts,
        error_message: "",
        is_loading: false,
        has_loaded_content: true,
    }),

    fromError: (error: Error): LinkFieldPresenter => ({
        linked_artifacts: [],
        error_message: error.message,
        is_loading: false,
        has_loaded_content: true,
    }),
};
