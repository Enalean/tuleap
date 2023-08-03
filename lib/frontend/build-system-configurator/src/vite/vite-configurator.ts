/**
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

import path from "path";
import type { UserConfigExport } from "vitest/config";
import {
    configDefaults as config_defaults_vitest,
    defineConfig as viteDefineConfig,
} from "vitest/config";
import type { BuildOptions, CSSOptions, ServerOptions, UserConfig } from "vite";
import type { CoverageOptions } from "vitest";
import { browserlist_config, esbuild_target } from "../browserslist_config";
import autoprefixer from "autoprefixer";

export type TuleapSpecificConfiguration = {
    plugin_name: string;
    sub_app_name?: string;
};

type OverloadedBuildOptions = Omit<BuildOptions, "reportCompressedSize" | "minify" | "target">;
type OverloadedServerOptions = Omit<ServerOptions, "fs">;
type OverloadedCSSOptions = Omit<CSSOptions, "postcss">;
type UserConfigWithoutBuildAndServerAndTest = Omit<UserConfig, "build" | "server" | "test">;
type OverloadedUserConfig = UserConfigWithoutBuildAndServerAndTest & {
    build?: OverloadedBuildOptions;
} & {
    server?: OverloadedServerOptions;
} & { css?: OverloadedCSSOptions };

export function defineLibConfig(config: OverloadedUserConfig): UserConfigExport {
    const overloaded_build = { ...config.build };
    overloaded_build.rollupOptions = {
        ...overloaded_build.rollupOptions,
        // Force output of __esModule property, otherwise Jest mocks of libs are broken
        output: { esModule: true },
    };
    return defineBaseConfig({ ...config, build: overloaded_build });
}

type OverloadedBuildAppOptions = Omit<
    OverloadedBuildOptions,
    "lib" | "manifest" | "outDir" | "emptyOutDir"
>;
type OverloadedAppUserConfig = Omit<UserConfigWithoutBuildAndServerAndTest, "base"> & {
    build?: OverloadedBuildAppOptions;
} & {
    server?: OverloadedServerOptions;
};

const getBaseDir = (tuleap_config: TuleapSpecificConfiguration): { base: string } => {
    if (tuleap_config.sub_app_name !== undefined) {
        return {
            base: `/assets/${tuleap_config.plugin_name}/${tuleap_config.sub_app_name}/`,
        };
    }
    return {
        base: `/assets/${tuleap_config.plugin_name}/`,
    };
};

export function defineAppConfig(
    tuleap_config: TuleapSpecificConfiguration,
    config: OverloadedAppUserConfig
): UserConfigExport {
    const { base } = getBaseDir(tuleap_config);

    const overridable_build_default: BuildOptions = {
        chunkSizeWarningLimit: 3000,
    };

    return defineBaseConfig({
        ...config,
        base,
        build: {
            ...overridable_build_default,
            ...config.build,
            manifest: true,
            emptyOutDir: true,
            outDir: "./frontend-assets/",
        },
    });
}

const TEST_OUTPUT_DIRECTORY = "./js-test-results/";

function defineBaseConfig(config: UserConfig): UserConfigExport {
    const test_reporters = ["default"];

    if (process.env.CI_MODE === "true") {
        test_reporters.push("junit");
    }
    let test_coverage: CoverageOptions = {
        reportsDirectory: TEST_OUTPUT_DIRECTORY,
    };
    if (process.env.COLLECT_COVERAGE === "true") {
        test_coverage = {
            ...test_coverage,
            enabled: true,
            reporter: ["text-summary", "cobertura"],
        };
    }

    return viteDefineConfig({
        ...config,
        build: {
            ...config.build,
            reportCompressedSize: false,
            minify: "esbuild",
            target: esbuild_target,
        },
        css: {
            ...config.css,
            postcss: {
                plugins: [autoprefixer({ overrideBrowserslist: browserlist_config })],
            },
        },
        server: {
            fs: {
                allow: [__dirname + "/../../../../../"],
                strict: true,
            },
        },
        test: {
            watch: false,
            restoreMocks: true,
            css: true,
            environment: "jsdom",
            include: ["**/?(*.)+(test).{js,ts}"],
            exclude: [
                ...config_defaults_vitest.exclude,
                "**/vendor/**",
                "**/assets/**",
                "**/frontend-assets/**",
                "**/tests/**",
                "**/*.d.ts",
                "./scripts/lib/**",
                "**/js-test-results/**",
            ],
            setupFiles: [path.resolve(__dirname, "../../src/vitest/setup-snapshot-serializer.ts")],
            onConsoleLog: (log: string, type: "stdout" | "stderr"): void => {
                if (type === "stderr") {
                    throw new Error(`Console warnings and errors are not allowed, got ${log}`);
                }
            },
            reporters: test_reporters,
            outputFile: {
                junit: TEST_OUTPUT_DIRECTORY + "/junit.xml",
            },
            coverage: test_coverage,
        },
    });
}
