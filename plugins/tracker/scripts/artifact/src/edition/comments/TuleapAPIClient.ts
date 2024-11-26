/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

export interface FetchInterface {
    fetch: typeof global.fetch;
}

export interface TuleapAPIClient {
    postComment(changeset_id: string, body: string, format: TextFieldFormat): Promise<string>;
}

export const TuleapAPIClient = (doc: Document, fetcher: FetchInterface): TuleapAPIClient => ({
    postComment(changeset_id, body, format): Promise<string> {
        const url = new URL(doc.URL);
        url.searchParams.append("func", "update-comment");
        url.searchParams.append("changeset_id", changeset_id);
        url.searchParams.append("content", body);
        url.searchParams.append("comment_format", format);
        return fetcher.fetch(url, { method: "POST" }).then((response) => response.text());
    },
});
