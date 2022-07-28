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
import { configDefaults as config_defaults_vitest } from "vitest/config";
import type { BuildOptions, CSSOptions, ServerOptions, UserConfig } from "vite";
import { defineConfig as viteDefineConfig } from "vitest/config";
import type { C8Options } from "vitest";
import { browserlist_config, esbuild_target } from "../browserslist_config";
import autoprefixer from "autoprefixer";

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
    return defineBaseConfig(config);
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

export function defineAppConfig(
    app_name: string,
    config: OverloadedAppUserConfig
): UserConfigExport {
    const overridable_build_default: BuildOptions = {
        chunkSizeWarningLimit: 3000,
    };

    return defineBaseConfig({
        ...config,
        base: `/assets/${app_name}/`,
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
    let test_coverage: C8Options = {
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
            setupFiles: [
                path.resolve(__dirname, "../../src/vitest/setup-snapshot-serializer.ts"),
                path.resolve(__dirname, "../../src/vitest/fail-console-error-warning.ts"),
            ],
            reporters: test_reporters,
            outputFile: {
                junit: TEST_OUTPUT_DIRECTORY + "/junit.xml",
            },
            coverage: test_coverage,
        },
    });
}
