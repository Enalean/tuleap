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
        // The whole retrieval of the CSRF token is quite ugly
        // The proper solution would be to have a REST endpoint to update these comments
        let csrf_token = "not_csrf_token_found_in_page";
        const artifact_forms = doc.body.getElementsByClassName("artifact-form");
        for (const artifact_form of artifact_forms) {
            if (!(artifact_form instanceof HTMLFormElement)) {
                continue;
            }
            const form_challenge = new FormData(artifact_form).get("challenge");
            if (form_challenge !== null) {
                csrf_token = form_challenge.toString();
                break;
            }
        }

        const url_search_params = new URLSearchParams();
        url_search_params.append("func", "update-comment");
        url_search_params.append("changeset_id", changeset_id);
        url_search_params.append("content", body);
        url_search_params.append("comment_format", format);
        url_search_params.append("challenge", csrf_token);
        return fetcher
            .fetch(doc.URL, {
                body: url_search_params,
                method: "POST",
            })
            .then((response) => response.text());
    },
});
