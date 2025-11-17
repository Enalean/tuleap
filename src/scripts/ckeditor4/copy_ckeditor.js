#! /usr/bin/env node
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const ckeditor4_source_directory = path.resolve(__dirname, "./node_modules/ckeditor4/");

const pkg = JSON.parse(fs.readFileSync(ckeditor4_source_directory + "/package.json"));
const ckeditor_version = pkg.version;

const frontend_assets_directory = path.resolve(__dirname, "./frontend-assets");
fs.rmSync(frontend_assets_directory, { recursive: true, force: true });

fs.mkdirSync(frontend_assets_directory);
const frontend_assets_directory_ckeditor4 =
    frontend_assets_directory + "/ckeditor-" + ckeditor_version;
fs.mkdirSync(frontend_assets_directory_ckeditor4);

const content_to_copy = [
    "/ckeditor.js",
    "/config.js",
    "/contents.css",
    "/styles.js",
    "/lang/",
    "/plugins/",
    "/skins/",
    "/vendor/",
];
for (const content of content_to_copy) {
    const source_path = ckeditor4_source_directory + content;
    fs.cpSync(source_path, frontend_assets_directory_ckeditor4 + content, {
        recursive: fs.lstatSync(source_path).isDirectory(),
    });
}

// Fake a Vite manifest to ease integration within Tuleap
fs.mkdirSync(frontend_assets_directory + "/.vite/");
fs.writeFileSync(
    frontend_assets_directory + "/.vite/manifest.json",
    JSON.stringify({
        "ckeditor.js": { file: `ckeditor-${ckeditor_version}/ckeditor.js` },
    }),
);
