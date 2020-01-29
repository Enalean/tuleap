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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

export function fetchCrossPluginComponents(callbacks) {
    const promises = [];
    for (const callback of callbacks) {
        promises.push(getCallbackForJSONP(callback));
    }
    return Promise.all(promises);
}

function getCallbackForJSONP(first_callback) {
    return new Promise((resolve, reject) => {
        // We must create the callback function on window so that the JSONP asset script can call it
        window[first_callback.callback_name] = function(module) {
            if (!("french_translations" in module) || !("components" in module)) {
                reject(
                    `The provided module should have keys named "french_translations" and "components"`
                );
                return;
            }
            resolve(module);
        };
        dynamicImportBecauseOfIE11(first_callback.asset_url);
    });
}

function dynamicImportBecauseOfIE11(asset_url) {
    const script = document.createElement("script");
    script.type = "text/javascript";
    script.src = asset_url;
    document.body.append(script);
}
