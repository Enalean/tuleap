/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { StorybookConfig } from "@storybook/web-components-vite";

const config: StorybookConfig = {
    framework: "@storybook/web-components-vite",
    stories: [
        "../stories/**/*.mdx",
        "../stories/**/*.stories.ts",
        "../../../../lib/frontend/*/src/**/*.mdx",
        "../../../../lib/frontend/*/src/**/*.stories.ts",
        "../*/src/**/*.mdx",
        "../*/src/**/*.stories.ts",
        "../../../../plugins/*/scripts/*/src/**/*.mdx",
        "../../../../plugins/*/scripts/*/src/**/*.stories.ts",
    ],
    addons: [
        "@storybook/addon-essentials",
        "@storybook/addon-interactions",
        "@storybook/addon-links",
        "@storybook/addon-themes",
    ],
};

export default config;
