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
    getNoResultFoundEmptyState,
    getSearchResultsGroupFootMessage,
    getSearchResultsGroupLabel,
} from "../../../../../gettext-catalog";
import type { VerifyIsAlreadyLinked } from "../../../../../domain/fields/link-field/VerifyIsAlreadyLinked";
import type { LinkableArtifact } from "../../../../../domain/fields/link-field/LinkableArtifact";
import { LinkSelectorItemProxy } from "./LinkSelectorItemProxy";

export const SearchResultsGroup = {
    buildEmpty: (): GroupOfItems => ({
        label: getSearchResultsGroupLabel(),
        empty_message: getNoResultFoundEmptyState(),
        items: [],
        is_loading: false,
        footer_message: "",
    }),

    buildLoadingState: (): GroupOfItems => ({
        label: getSearchResultsGroupLabel(),
        empty_message: "",
        items: [],
        is_loading: true,
        footer_message: "",
    }),

    fromSearchResults: (
        link_verifier: VerifyIsAlreadyLinked,
        search_results: readonly LinkableArtifact[],
    ): GroupOfItems => ({
        label: getSearchResultsGroupLabel(),
        empty_message: getNoResultFoundEmptyState(),
        items: search_results.map((artifact) =>
            LinkSelectorItemProxy.fromLinkableArtifact(link_verifier, artifact),
        ),
        is_loading: false,
        footer_message: getSearchResultsGroupFootMessage(),
    }),
};
