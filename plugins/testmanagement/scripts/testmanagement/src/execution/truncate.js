/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import unified from "unified";
import parse from "rehype-parse";
import toHtml from "hast-util-to-html";
import map from "unist-util-map";
import remove from "unist-util-remove";
import toString from "hast-util-to-string";

export function truncateHTML(content, max_length, placeholder_image_text) {
    const tree = unified().use(parse, { fragment: true }).parse(content);

    let counter = 0;
    let images_counter = 0;
    let p_counter = 0;
    const truncated_tree = map(tree, function (node) {
        if (counter >= max_length) {
            return null;
        }

        if (node.type === "element" && node.tagName === "img") {
            images_counter++;
            return null;
        }

        if (node.type === "element" && toString(node).trim().length === 0) {
            return null;
        }

        if (node.type === "element" && node.tagName === "p") {
            p_counter++;
            if (p_counter > 1) {
                return null;
            }
        }

        if (node.type !== "text") {
            return node;
        }

        if (counter + node.value.length < max_length) {
            counter += node.value.length;
            return node;
        }

        const nb_addable = max_length - counter;
        counter = max_length;

        return Object.assign({}, node, {
            value: node.value.substr(0, nb_addable) + "…",
        });
    });

    remove(truncated_tree, (node) => {
        return typeof node.type === "undefined";
    });

    if (images_counter > 0 && toString(truncated_tree).trim().length === 0) {
        return "<p><i>" + placeholder_image_text + "</i></p>";
    }

    return toHtml(truncated_tree);
}
