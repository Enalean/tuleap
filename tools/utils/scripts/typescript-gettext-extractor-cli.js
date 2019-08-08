#!/usr/bin/env node
/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

/* eslint-disable no-console */

const fs = require("fs");
const minimist = require("minimist");
const ts = require("typescript");

const constants = require("./gettext/constants.js");
const extract = require("./gettext/extract.js");
const { GettextExtractor, JsExtractors } = require("gettext-extractor");
const gettext_extractor = new GettextExtractor();

const PROGRAM_NAME = "typescript-extractor";
const ALLOWED_EXTENSIONS = ["ts"];

// Process arguments
const argv = minimist(process.argv.slice(2));
const files = argv._.sort() || [];
const quietMode = argv.quiet || false;
const outputFile = argv.output || null;
const startDelimiter =
    argv.startDelimiter === undefined ? constants.DEFAULT_DELIMITERS.start : argv.startDelimiter;
const endDelimiter =
    argv.endDelimiter === undefined ? constants.DEFAULT_DELIMITERS.end : argv.endDelimiter;
const extraFilter = argv.filter || false;
const filterPrefix = argv.filterPrefix || constants.DEFAULT_FILTER_PREFIX;

if (!quietMode && (!files || files.length === 0)) {
    console.log(
        "Usage:\n\tgettext-extract [--filterPrefix FILTER-PREFIX] [--output OUTFILE] <FILES>"
    );
    process.exit(1);
}

function _getExtraNames(extraEntities, defaultEntities) {
    let attributes = defaultEntities.slice();
    if (extraEntities) {
        if (typeof extraEntities === "string") {
            // Only one extra attribute was passed.
            attributes.push(extraEntities);
        } else {
            // Multiple extra attributes were passed.
            attributes = attributes.concat(extraEntities);
        }
    }
    return attributes;
}

function _removeDuplicatePOTHeader(pot_string) {
    const pot_lines = pot_string.split("\n");
    return pot_lines.slice(3, pot_lines.length).join("\n");
}

const attributes = [];
const filters = _getExtraNames(extraFilter, constants.DEFAULT_FILTERS);

// Extract strings
const extractor = new extract.Extractor({
    lineNumbers: true,
    attributes,
    filters,
    filterPrefix,
    startDelimiter,
    endDelimiter
});

const script_gettext_extractor = gettext_extractor.createJsParser([
    JsExtractors.callExpression("gettext_provider.gettext", { arguments: { text: 0 } }),
    JsExtractors.callExpression("gettext_provider.ngettext", {
        arguments: { text: 0, textPlural: 1 }
    }),
    JsExtractors.callExpression("gettext_provider.pgettext", {
        arguments: { context: 0, text: 1 }
    }),
    JsExtractors.callExpression("gettext_provider.npgettext", {
        arguments: { context: 0, text: 1, textPlural: 2 }
    })
]);

files.forEach(function(filename) {
    let file = filename;
    const ext = file.split(".").pop();
    if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
        console.log(`[${PROGRAM_NAME}] will not extract: '${filename}' (invalid extension)`);
        return;
    }
    console.log(`[${PROGRAM_NAME}] extracting: '${filename}`);
    try {
        let data = fs.readFileSync(file, { encoding: "utf-8" }).toString();

        script_gettext_extractor.parseString(data, filename, {
            scriptKind: ts.ScriptKind.TS
        });
    } catch (e) {
        console.error(`[${PROGRAM_NAME}] could not read: '${filename}`);
        console.trace(e);
        process.exit(1);
    }
});

const result = extractor.toString() + _removeDuplicatePOTHeader(gettext_extractor.getPotString());
if (outputFile) {
    fs.writeFileSync(outputFile, result);
} else {
    console.log(result);
}
