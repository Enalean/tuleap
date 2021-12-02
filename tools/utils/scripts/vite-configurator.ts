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

import type { BuildOptions, CSSOptions, ServerOptions, UserConfig, UserConfigExport } from "vite";
import { defineConfig as viteDefineConfig } from "vite";
export { createPOGettextPlugin } from "./rollup-plugin-po-gettext";
import { browserlist_config, esbuild_target } from "./browserslist_config";
import type { UserPluginConfig as PluginCheckerConfig } from "vite-plugin-checker";
import checker from "vite-plugin-checker";
import dts from "vite-dts";
import path from "path";
import autoprefixer from "autoprefixer";

type OverloadedBuildOptions = Omit<BuildOptions, "brotliSize" | "minify" | "target">;
type OverloadedServerOptions = Omit<ServerOptions, "fs">;
type OverloadedCSSOptions = Omit<CSSOptions, "postcss">;
type UserConfigWithoutBuildAndServer = Omit<UserConfig, "build" | "server">;
type OverloadedUserConfig = UserConfigWithoutBuildAndServer & { build?: OverloadedBuildOptions } & {
    server?: OverloadedServerOptions;
} & { css?: OverloadedCSSOptions };

export function defineConfig(
    config: OverloadedUserConfig,
    typechecks: null | Pick<PluginCheckerConfig, "typescript" | "vls" | "vueTsc">
): UserConfigExport {
    const plugins = config.plugins ?? [];
    if (typechecks !== null && config.build?.lib) {
        plugins.push(dts());
    }
    return defineBaseConfig({ ...config, plugins }, typechecks);
}

type OverloadedBuildAppOptions = Omit<
    OverloadedBuildOptions,
    "lib" | "manifest" | "outDir" | "emptyOutDir"
>;
type OverloadedAppUserConfig = Omit<UserConfigWithoutBuildAndServer, "base"> & {
    build?: OverloadedBuildAppOptions;
} & {
    server?: OverloadedServerOptions;
};

export function defineAppConfig(
    app_name: string,
    config: OverloadedAppUserConfig,
    typechecks: null | Pick<PluginCheckerConfig, "typescript" | "vls" | "vueTsc">
): UserConfigExport {
    const overridable_build_default: BuildOptions = {
        chunkSizeWarningLimit: 3000,
    };

    return defineBaseConfig(
        {
            ...config,
            base: `/assets/${app_name}/`,
            build: {
                ...overridable_build_default,
                ...config.build,
                manifest: true,
                emptyOutDir: true,
                outDir: path.resolve(__dirname, `../../../src/www/assets/${app_name}/`),
            },
        },
        typechecks
    );
}

function defineBaseConfig(
    config: UserConfig,
    typechecks: null | Pick<PluginCheckerConfig, "typescript" | "vls" | "vueTsc">
): UserConfigExport {
    const plugins = config.plugins ?? [];
    if (typechecks !== null) {
        plugins.push(checker(typechecks));
    }
    return viteDefineConfig({
        ...config,
        build: {
            ...config.build,
            brotliSize: false,
            minify: "esbuild",
            target: esbuild_target,
        },
        css: {
            ...config.css,
            postcss: {
                plugins: [autoprefixer({ overrideBrowserslist: browserlist_config })],
            },
        },
        plugins,
        server: {
            fs: {
                allow: [__dirname + "/../../../"],
                strict: true,
            },
        },
    });
}
