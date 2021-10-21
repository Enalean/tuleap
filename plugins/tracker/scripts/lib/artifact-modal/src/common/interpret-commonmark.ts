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

import type { TextFieldFormat } from "../../../../constants/fields-constants";

import { postInterpretCommonMark } from "../api/tuleap-api";
export interface CommonMarkInterpreter {
    format: TextFieldFormat;
    projectId: number;
    interpreted_commonmark: string;
    is_in_preview_mode: boolean;
    is_preview_loading: boolean;
    has_error: boolean;
    error_message: string;
}

export const interpretCommonMark = async (
    host: CommonMarkInterpreter,
    content: string
): Promise<void> => {
    host.has_error = false;
    host.error_message = "";

    if (host.is_in_preview_mode) {
        host.is_in_preview_mode = false;
        return;
    }
    try {
        host.is_preview_loading = true;
        host.interpreted_commonmark = await postInterpretCommonMark(content, host.projectId);
    } catch (error) {
        host.has_error = true;
        if (error instanceof Error) {
            host.error_message = error.message;
        }
    } finally {
        host.is_in_preview_mode = true;
        host.is_preview_loading = false;
    }
};
