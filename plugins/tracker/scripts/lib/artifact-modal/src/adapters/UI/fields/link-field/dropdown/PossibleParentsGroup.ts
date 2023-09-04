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

import type { GroupOfItems } from "@tuleap/lazybox";
import {
    getPossibleParentsEmptyState,
    getPossibleParentsLabel,
} from "../../../../../gettext-catalog";
import type { LinkableArtifact } from "../../../../../domain/fields/link-field/LinkableArtifact";
import type { VerifyIsAlreadyLinked } from "../../../../../domain/fields/link-field/VerifyIsAlreadyLinked";
import { LinkSelectorItemProxy } from "./LinkSelectorItemProxy";

export const PossibleParentsGroup = {
    buildLoadingState: (): GroupOfItems => ({
        label: getPossibleParentsLabel(),
        empty_message: "",
        items: [],
        is_loading: true,
        footer_message: "",
    }),

    fromPossibleParents: (
        link_verifier: VerifyIsAlreadyLinked,
        possible_parents: readonly LinkableArtifact[],
    ): GroupOfItems => ({
        label: getPossibleParentsLabel(),
        empty_message: getPossibleParentsEmptyState(),
        items: possible_parents.map((artifact) =>
            LinkSelectorItemProxy.fromLinkableArtifact(link_verifier, artifact),
        ),
        is_loading: false,
        footer_message: "",
    }),

    buildEmpty: (): GroupOfItems => ({
        label: getPossibleParentsLabel(),
        empty_message: getPossibleParentsEmptyState(),
        items: [],
        is_loading: false,
        footer_message: "",
    }),
};
