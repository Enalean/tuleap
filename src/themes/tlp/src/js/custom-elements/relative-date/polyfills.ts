/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { loadCustomElementsPolyfillWhenNeeded } from "../../../../../../scripts/tuleap/custom-elements/custom-elements-polyfill-ie11";

export async function loadPolyfillsWhenNeeded(): Promise<void> {
    await Promise.all([
        loadCustomElementsPolyfillWhenNeeded(),
        loadIntlRelativeTimePolyfillWhenNeeded(),
    ]);
}

export async function loadIntlRelativeTimePolyfillWhenNeeded(): Promise<void> {
    if (typeof Intl !== "undefined" && "RelativeTimeFormat" in Intl) {
        return;
    }

    const polyfill_promises = [];

    // Expressions are used when importing the locale data to TypeScript to bail out of the verification so it does not
    // complain about missing types.
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-getcanonicallocales/polyfill"
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-pluralrules/polyfill"
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-pluralrules/locale-data/en" +
                ""
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-pluralrules/locale-data/fr" +
                ""
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-numberformat/polyfill"
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-numberformat/locale-data/en" +
                ""
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-numberformat/locale-data/fr" +
                ""
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-relativetimeformat/polyfill"
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-relativetimeformat/locale-data/en" +
                ""
        )
    );
    polyfill_promises.push(
        import(
            /* webpackChunkName: "polyfill-intl-relativetimeformat" */ "@formatjs/intl-relativetimeformat/locale-data/fr" +
                ""
        )
    );

    await Promise.all(polyfill_promises);
}
