/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { UserConfig } from "vitest/config";
import { beforeEach, describe, it, expect } from "vitest";
import type { TuleapSpecificConfiguration } from "./vite-configurator";
import { defineAppConfig } from "./vite-configurator";

describe(`vite-configurator`, () => {
    const PLUGIN_NAME = "belting";
    const APP_NAME = "partitively_chordata";
    let tuleap_configuration: TuleapSpecificConfiguration;

    beforeEach(() => {
        tuleap_configuration = { plugin_name: PLUGIN_NAME };
    });

    const getConfig = async (): Promise<UserConfig> => {
        const config = await defineAppConfig(tuleap_configuration, {});

        if (typeof config === "function") {
            throw new Error("Did not expect returned configuration to be a function");
        }
        return config;
    };

    it(`sets the given plugin_name in base path configuration`, async () => {
        const config = await getConfig();

        expect(config.base).toBe(`/assets/${PLUGIN_NAME}/`);
        expect(config.build?.outDir).toBe("./frontend-assets/");
    });

    it(`given an outDir, it will replace the outDir configuration
        and its final directory will be added to the end of the base path configuration`, async () => {
        tuleap_configuration.outDir = `../../frontend-assets/${APP_NAME}/`;

        const config = await getConfig();

        expect(config.base).toBe(`/assets/${PLUGIN_NAME}/${APP_NAME}/`);
        expect(config.build?.outDir).toBe(tuleap_configuration.outDir);
    });
});
