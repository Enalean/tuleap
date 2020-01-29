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

import { fetchCrossPluginComponents } from "./cross-plugin-component-importer.js";

describe("cross-plugin-component-importer", () => {
    afterEach(() => {
        const scripts = Array.from(document.scripts);
        scripts.forEach(script => script.remove());
        delete window.MyExternalComponent;
        delete window.MyExternalComponentFromAnotherPlugin;
    });

    it("returns the asset URL and the callback name", async () => {
        const module = {
            french_translations: { "IE 11 is awesome": "C'est faux" },
            components: [{ name: "MyComponent" }]
        };
        const module2 = {
            french_translations: { "straight outta Compton": "tout droit sorti de Compton" },
            components: [{ name: "MySecond" }]
        };

        const callbacks = [
            {
                callback_name: "MyExternalComponent",
                asset_url: "/assets/my-awesome-asset.js"
            },
            {
                callback_name: "MyExternalComponentFromAnotherPlugin",
                asset_url: "/assets/juicy.js"
            }
        ];

        const promise = fetchCrossPluginComponents(callbacks);
        // Simulate JSONP call that would be done by the scripts (/assets/my-awesome-asset.js & co.)
        // Without these calls, the promise is never resolved
        window.MyExternalComponent(module);
        window.MyExternalComponentFromAnotherPlugin(module2);

        const modules = await promise;
        expect(modules).toEqual([module, module2]);
        expect(document.scripts.length).toEqual(2);
        expect(document.scripts[0].outerHTML).toEqual(
            '<script type="text/javascript" src="/assets/my-awesome-asset.js"></script>'
        );
        expect(document.scripts[1].outerHTML).toEqual(
            '<script type="text/javascript" src="/assets/juicy.js"></script>'
        );
    });
    it("returns nothing if there is no component", async () => {
        const module = { french_translations: { "IE 11 is awesome": "C'est faux" } };
        const callbacks = [
            {
                callback_name: "MyExternalComponent",
                asset_url: "/assets/my-awesome-asset.js"
            }
        ];

        const promise = fetchCrossPluginComponents(callbacks);

        window.MyExternalComponent(module);

        await expect(promise).rejects.toEqual(
            `The provided module should have keys named "french_translations" and "components"`
        );
        expect(document.scripts.length).toEqual(1);
    });
});
