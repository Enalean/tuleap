/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { VueGettextProvider } from "./vue-gettext-provider";

export async function handleError(
    rest_error: unknown,
    gettext_provider: VueGettextProvider,
): Promise<string> {
    if (!(rest_error instanceof FetchWrapperError)) {
        return gettext_provider.$gettext("Oops, an error occurred!");
    }

    try {
        const json = await rest_error.response.json();

        if (!Object.prototype.hasOwnProperty.call(json, "error")) {
            return gettext_provider.$gettext("Oops, an error occurred!");
        }

        if (Object.prototype.hasOwnProperty.call(json.error, "i18n_error_message")) {
            return json.error.i18n_error_message;
        }

        return json.error.code + " " + json.error.message;
    } catch (error) {
        return gettext_provider.$gettext("Oops, an error occurred!");
    }
}
