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

const fs = require("fs");
const path = require("path");
const ts = require("typescript");
const { JsExtractors } = require("gettext-extractor");

const { log } = require("./log.js");
const {
    ALLOWED_EXTENSIONS,
    DIRECTIVE_NAME,
    COMPONENT_NAME,
    PLURAL_STRING_DIRECTIVE_NAME,
} = require("./constants.js");

const INTERPOLATED_TEXT_NODE = 2;
const TEXT_NODE = 3;

function extractFileSync(file_path, gettext_extractor, vue_parser) {
    const ext = path.extname(file_path).slice(1);
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
        log(`will not extract: '${file_path}' (invalid extension)`);
        throw new Error(`Invalid extension ${ext} for file: ${file_path}`);
    }

    log(`extracting: '${file_path}`);
    const gettext_parser = createParser(gettext_extractor);

    if (ext === "vue") {
        const file = fs.readFileSync(file_path, { encoding: "utf-8" }).toString();
        extractFromVueFile(file, file_path, gettext_extractor, gettext_parser, vue_parser);
    } else if (ext === "js" || ext === "ts") {
        gettext_parser.parseFile(file_path);
    }
}

function createParser(gettext_extractor) {
    return gettext_extractor.createJsParser([
        JsExtractors.callExpression(
            [
                "[this].$gettext",
                "[this].gettext_provider.gettext",
                "$gettext",
                "gettext_provider.$gettext",
                "[this].gettext_provider.$gettext",
                "gettext_provider.gettext",
            ],
            {
                arguments: { text: 0 },
            },
        ),
        JsExtractors.callExpression(
            [
                "[this].$ngettext",
                "$ngettext",
                "gettext_provider.$ngettext",
                "gettext_provider.ngettext",
            ],
            {
                arguments: { text: 0, textPlural: 1 },
            },
        ),
        JsExtractors.callExpression(
            ["[this].$pgettext", "$pgettext", "gettext_provider.$pgettext"],
            {
                arguments: { context: 0, text: 1 },
            },
        ),
        JsExtractors.callExpression(
            ["[this].$npgettext", "$npgettext", "gettext_provider.$npgettext"],
            {
                arguments: { context: 0, text: 1, textPlural: 2 },
            },
        ),
    ]);
}

function extractFromVueFile(file, file_path, gettext_extractor, gettext_parser, vue_parser) {
    const sfc_descriptor = vue_parser.parse(file);

    if (sfc_descriptor.script !== null) {
        extractFromVueScript(sfc_descriptor.script);
    }
    if (sfc_descriptor.scriptSetup !== undefined && sfc_descriptor.scriptSetup !== null) {
        extractFromVueScript(sfc_descriptor.scriptSetup);
    }
    if (sfc_descriptor.template !== null) {
        extractFromTemplate(sfc_descriptor.template, vue_parser);
    }

    function extractFromVueScript(script_block) {
        const lang = script_block.lang !== null ? script_block.lang : "js";
        const scriptKind = lang === "ts" ? ts.ScriptKind.TS : ts.ScriptKind.JS;

        gettext_parser.parseString(script_block.content, undefined, {
            scriptKind,
        });
    }

    function extractFromTemplate(template_block, vue_parser) {
        const compiled = vue_parser.compile(template_block.content);
        if (compiled.errors.length > 0) {
            compiled.errors.forEach((error) => log(error));
            throw new Error(`Error during Vue template compilation for file: ${file_path}`);
        }
        const scriptKind = ts.ScriptKind.JS;
        // vue3-gettext deprecates the usage of the translate component and the v-translate directive
        // so when parsing Vue 3 code we skip the parsing the template AST and just look at the generated JS code
        if (compiled.code !== undefined) {
            gettext_parser.parseString(
                compiled.code.replaceAll("_ctx.", "gettext_provider."),
                undefined,
                {
                    scriptKind,
                },
            );
        } else {
            gettext_parser.parseString(compiled.render, undefined, {
                scriptKind,
            });
            parseNode(compiled.ast);
        }
    }

    function parseNode(node) {
        if (isTextOrInterpolatedTextNode(node)) {
            return;
        }

        if ("directives" in node && doesNodeHaveAnySupportedDirective(node)) {
            extractDirective(node, gettext_extractor);
        }

        if (node.tag === COMPONENT_NAME) {
            extractComponent(node, gettext_extractor);
        }

        if ("attrs" in node) {
            node.attrs.forEach((attr) => {
                gettext_parser.parseString(attr.value);
            });
        }

        if ("ifConditions" in node) {
            node.ifConditions
                .map((condition) => condition.block)
                .filter((block) => block !== node)
                .forEach(parseNode);
        }

        if ("children" in node) {
            node.children.filter((node) => !isTextOrInterpolatedTextNode(node)).forEach(parseNode);
        }
    }
}

function extractDirective(node, gettext_extractor) {
    const [text_child] = node.children.filter(isTextOrInterpolatedTextNode);
    if (text_child === undefined) {
        return;
    }
    gettext_extractor.addMessage({ text: text_child.text.trim() });
}

function extractComponent(node, gettext_extractor) {
    const [text_child] = node.children.filter(isTextOrInterpolatedTextNode);
    if (text_child === undefined) {
        return;
    }
    const text = text_child.text.trim();

    if ("attrsMap" in node && node.attrsMap[PLURAL_STRING_DIRECTIVE_NAME]) {
        const plural_string = node.attrsMap[PLURAL_STRING_DIRECTIVE_NAME];
        gettext_extractor.addMessage({
            text,
            textPlural: plural_string.trim(),
        });
        return;
    }
    gettext_extractor.addMessage({ text });
}

const isTextOrInterpolatedTextNode = (node) =>
    node.type === TEXT_NODE || node.type === INTERPOLATED_TEXT_NODE;

const doesNodeHaveAnySupportedDirective = (node) =>
    node.directives.some((directive) => directive.rawName === DIRECTIVE_NAME);

module.exports = {
    extractFileSync,
};
