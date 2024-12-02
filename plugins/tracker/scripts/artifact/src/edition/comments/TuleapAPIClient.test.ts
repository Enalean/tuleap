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

import { TuleapAPIClient } from "./TuleapAPIClient";
import { TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";

describe(`TuleapAPIClient`, () => {
    describe(`postComment()`, () => {
        it(`will POST to the current artifact URL with params and will return the response text`, async () => {
            const doc = { URL: "https://example.com/plugins/tracker/?aid=806" } as Document;
            const comment_body = "politically puncticular";
            const expected_comment_html = `<div><p>${comment_body}</p></div>`;
            const fetchStub = (): Promise<Response> =>
                Promise.resolve({ text: () => Promise.resolve(expected_comment_html) } as Response);
            const fetcher = {
                fetch: fetchStub as typeof global.fetch,
            };
            const fetch = jest.spyOn(fetcher, "fetch");
            const client = TuleapAPIClient(doc, fetcher);

            const changeset_id = "690";
            const comment_format = TEXT_FORMAT_COMMONMARK;
            const result = await client.postComment(changeset_id, comment_body, comment_format);

            expect(fetch).toHaveBeenCalled();
            const url = fetch.mock.calls[0][0];
            const request_init = fetch.mock.calls[0][1];
            expect(url.toString()).toBe(
                "https://example.com/plugins/tracker/?aid=806&func=update-comment&changeset_id=" +
                    changeset_id +
                    "&content=politically+puncticular" +
                    "&comment_format=" +
                    comment_format,
            );
            expect(request_init?.method).toBe("POST");
            expect(result).toBe(expected_comment_html);
        });
    });
});
