/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

// IE11 is the only browser supported by Tuleap that does not support custom elements

export async function loadCustomElementsPolyfillWhenNeeded(): Promise<void> {
    const existing_custom_elements = window.customElements;

    // See https://github.com/webcomponents/polyfills/blob/%40webcomponents/custom-elements%401.4.2/packages/custom-elements/ts_src/custom-elements.ts#L48-L50
    if (
        !existing_custom_elements ||
        typeof existing_custom_elements.define !== "function" ||
        typeof existing_custom_elements.get !== "function"
    ) {
        // TypeScript complains when processing the dynamic import because the polyfill does not expose its typings
        // (it's not really important in this context) so we transform the import into an expression to force TypeScript
        // to bail out. Webpack is capable to resolve it (and to optimize it).
        await import(
            // eslint-disable-next-line no-useless-concat
            /* webpackChunkName: "polyfill-custom-elements" */ "@webcomponents/custom-elements" + ""
        );
    }
}
