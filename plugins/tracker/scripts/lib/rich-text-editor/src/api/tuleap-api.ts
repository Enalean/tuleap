/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import { post } from "@tuleap/tlp-fetch";

export function postMarkdown(markdown: string, project_id: string): Promise<string> {
    const form_data = new FormData();
    form_data.set("content", markdown);
    return post(encodeURI(`/project/${project_id}/interpret-commonmark`), {
        body: form_data,
    }).then(
        (response) => response.text(),
        (error: FetchWrapperError) =>
            error.response.text().then((error_text) => {
                //Re-throw the error to trigger the next .catch()
                throw new Error(error_text);
            }),
    );
}
