/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { postFormWithTextResponse, uri } from "@tuleap/fetch-result";

export const fetchCommonMarkPreview = (
    project_id: number,
    commonmark: string,
): ResultAsync<string, unknown> => {
    const form_data = new FormData();
    form_data.set("content", commonmark);

    return postFormWithTextResponse(uri`/project/${project_id}/interpret-commonmark`, form_data);
};
