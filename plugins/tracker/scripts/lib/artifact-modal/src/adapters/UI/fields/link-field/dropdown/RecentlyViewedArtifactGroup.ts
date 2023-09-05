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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { GroupOfItems } from "@tuleap/lazybox";
import {
    getNoResultFoundEmptyState,
    getRecentlyViewedArtifactGroupLabel,
} from "../../../../../gettext-catalog";
import { LinkSelectorItemProxy } from "./LinkSelectorItemProxy";
import type { VerifyIsAlreadyLinked } from "../../../../../domain/fields/link-field/VerifyIsAlreadyLinked";
import type { LinkableArtifact } from "../../../../../domain/fields/link-field/LinkableArtifact";

export const RecentlyViewedArtifactGroup = {
    buildEmpty: (): GroupOfItems => ({
        label: getRecentlyViewedArtifactGroupLabel(),
        empty_message: getNoResultFoundEmptyState(),
        items: [],
        is_loading: false,
        footer_message: "",
    }),

    buildLoadingState: (): GroupOfItems => ({
        label: getRecentlyViewedArtifactGroupLabel(),
        empty_message: "",
        items: [],
        is_loading: true,
        footer_message: "",
    }),

    fromUserHistory: (
        link_verifier: VerifyIsAlreadyLinked,
        linkable_artifacts: readonly LinkableArtifact[],
    ): GroupOfItems => ({
        label: getRecentlyViewedArtifactGroupLabel(),
        empty_message: getNoResultFoundEmptyState(),
        items: linkable_artifacts.map((artifact) =>
            LinkSelectorItemProxy.fromLinkableArtifact(link_verifier, artifact),
        ),
        is_loading: false,
        footer_message: "",
    }),
};
