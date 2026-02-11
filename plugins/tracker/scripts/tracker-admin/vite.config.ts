/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import { vite } from "@tuleap/build-system-configurator";
import * as path from "node:path";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

export default vite.defineAppConfig(
    {
        plugin_name: path.basename(path.resolve(__dirname, "../..")),
        sub_app_name: path.basename(__dirname),
    },
    {
        plugins: [POGettextPlugin.vite()],
        build: {
            rollupOptions: {
                input: {
                    "done-semantic": path.resolve(__dirname, "src/semantics/status/done-picker.ts"),
                    "canned-responses": path.resolve(__dirname, "src/canned-responses.ts"),
                    "field-permissions": path.resolve(__dirname, "src/field-permissions.ts"),
                    "progress-semantic": path.resolve(
                        __dirname,
                        "src/semantics/progress/admin-selectors.ts",
                    ),
                    "semantics-homepage": path.resolve(__dirname, "src/semantics/homepage.ts"),
                    "status-semantic": path.resolve(
                        __dirname,
                        "src/semantics/status/status-picker.ts",
                    ),
                    TrackerAdminFields: path.resolve(__dirname, "src/TrackerAdminFields.js"),
                    datepicker: path.resolve(__dirname, "styles/datepicker.scss"),
                    colorpicker: path.resolve(__dirname, "styles/colorpicker.scss"),
                    notifications: path.resolve(__dirname, "src/notifications/notifications.ts"),
                    "update-notification-reminder": path.resolve(
                        __dirname,
                        "src/notifications/update-notification-reminder.ts",
                    ),
                    "delete-notification-reminder": path.resolve(
                        __dirname,
                        "src/notifications/delete-notification-reminder.ts",
                    ),
                    "notifications-style": path.resolve(__dirname, "styles/notifications.scss"),
                    hierarchy: path.resolve(__dirname, "styles/hierarchy.scss"),
                    "general-settings": path.resolve(__dirname, "src/general-settings.ts"),
                    "general-settings-style": path.resolve(
                        __dirname,
                        "styles/general-settings.scss",
                    ),
                    "global-rules": path.resolve(__dirname, "src/global-rules.ts"),
                    "global-rules-style": path.resolve(__dirname, "styles/global-rules.scss"),
                    webhooks: path.resolve(__dirname, "src/webhooks.ts"),
                    "webhooks-style": path.resolve(__dirname, "styles/webhooks.scss"),
                    "field-dependencies": path.resolve(__dirname, "src/field-dependencies.ts"),
                    "field-dependencies-style": path.resolve(
                        __dirname,
                        "styles/field-dependencies.scss",
                    ),
                },
            },
        },
    },
);
