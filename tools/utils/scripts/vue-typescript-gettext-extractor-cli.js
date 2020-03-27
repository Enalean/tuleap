#!/usr/bin/env node
/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

/* From https://github.com/Polyconseil/easygettext/ */

const minimist = require("minimist");
const { GettextExtractor } = require("gettext-extractor");

const { extractFileSync } = require("./gettext/file-extractor.js");
const { PROGRAM_NAME } = require("./gettext/constants.js");

const gettext_extractor = new GettextExtractor();

// Process arguments
const argv = minimist(process.argv.slice(2));
const files = argv._.sort() || [];
const quietMode = argv.quiet || false;
const outputFile = argv.output;

if (!quietMode && (!files || files.length === 0 || !outputFile)) {
    console.log(`Usage:\n\t${PROGRAM_NAME} --output OUTFILE <FILES>`);
    process.exit(1);
}

files.forEach((filename) => {
    try {
        extractFileSync(filename, gettext_extractor);
    } catch (e) {
        console.error(`[${PROGRAM_NAME}] could not read: '${filename}`);
        console.trace(e);
        process.exit(1);
    }
});

gettext_extractor.savePotFile(outputFile);
