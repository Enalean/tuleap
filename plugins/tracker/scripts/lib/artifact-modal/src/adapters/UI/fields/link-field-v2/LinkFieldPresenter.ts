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

import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";

export type LinkFieldPresenter = {
    readonly field_id: number;
    readonly label: string;
    readonly current_artifact_reference: ArtifactCrossReference | null;
    readonly allowed_types: CollectionOfAllowedLinksTypesPresenters;
};

export const LinkFieldPresenter = {
    fromFieldAndCrossReference: (
        field: ArtifactLinkFieldStructure,
        current_artifact_reference: ArtifactCrossReference | null
    ): LinkFieldPresenter => {
        const type_presenters =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                field.allowed_types.filter((type) => type.shortname === IS_CHILD_LINK_TYPE)
            );
        return {
            field_id: field.field_id,
            label: field.label,
            current_artifact_reference,
            allowed_types: type_presenters,
        };
    },
};
