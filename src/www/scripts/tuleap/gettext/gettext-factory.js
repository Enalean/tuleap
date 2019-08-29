/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { initGettext as init } from "./gettext-init.js";

let gettext_provider;

export async function initGettext(language, domain, load_translations_callback) {
    if (typeof gettext_provider === "undefined") {
        // eslint-disable-next-line require-atomic-updates
        gettext_provider = await init(language, domain, load_translations_callback);
    }

    return gettext_provider;
}

export function getGettextProvider() {
    if (typeof gettext_provider === "undefined") {
        throw new Error(`You need to initialize the provider first ! Call "initGettext"`);
    }

    return gettext_provider;
}
