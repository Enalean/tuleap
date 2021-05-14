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

import type { BuildOptions, ServerOptions, UserConfig, UserConfigExport } from "vite";
import { defineConfig as viteDefineConfig } from "vite";
export { createPOGettextPlugin } from "./rollup-plugin-po-gettext";
import { esbuild_target } from "./browserslist_config";

type OverloadedBuildOptions = Omit<BuildOptions, "brotliSize" | "minify" | "target">;
type OverloadedServerOptions = Omit<ServerOptions, "fsServe">;
type UserConfigWithoutBuildAndServer = Omit<UserConfig, "build" | "server">;
type OverloadedUserConfig = UserConfigWithoutBuildAndServer & { build?: OverloadedBuildOptions } & {
    server?: OverloadedServerOptions;
};

export function defineConfig(config: OverloadedUserConfig): UserConfigExport {
    return viteDefineConfig({
        ...config,
        build: {
            ...config.build,
            brotliSize: false,
            minify: "esbuild",
            target: esbuild_target,
        },
        server: {
            fsServe: {
                root: __dirname + "/../../../",
                strict: true,
            },
        },
    });
}
