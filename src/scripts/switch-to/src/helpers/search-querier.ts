/**
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
import type { ResultAsync } from "neverthrow";
import type { ItemDefinition } from "../type";
import type { Fault } from "@tuleap/fault";
import { decodeJSON, post } from "@tuleap/fetch-result";
import type { FullTextState } from "../stores/type";

export function query(
    url: string,
    keywords: string
): ResultAsync<FullTextState["fulltext_search_results"], Fault> {
    return getTheFirstPage(url, keywords).map(
        (results: ItemDefinition[]): FullTextState["fulltext_search_results"] =>
            deduplicate(results)
    );
}

function getTheFirstPage(url: string, keywords: string): ResultAsync<ItemDefinition[], Fault> {
    return post(url, {
        search_query: {
            keywords,
        },
    }).andThen((response) => decodeJSON<ItemDefinition[]>(response));
}

function deduplicate(results: ItemDefinition[]): FullTextState["fulltext_search_results"] {
    return results.reduce(
        (
            deduplicated_entries: FullTextState["fulltext_search_results"],
            entry: ItemDefinition
        ): FullTextState["fulltext_search_results"] => {
            if (typeof deduplicated_entries[entry.html_url] === "undefined") {
                deduplicated_entries[entry.html_url] = entry;
            }

            return deduplicated_entries;
        },
        {}
    );
}
