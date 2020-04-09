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

export function truncateHTML(content, expected_length) {
    const tree = unified().use(parse, { fragment: true }).parse(content);

    let counter = 0;
    const truncated_tree = map(tree, function (node) {
        if (counter >= expected_length) {
            return null;
        }

        if (node.type !== "text") {
            return node;
        }

        if (counter + node.value.length < expected_length) {
            counter += node.value.length;
            return node;
        }

        const nb_addable = expected_length - counter;
        counter = expected_length;

        return Object.assign({}, node, {
            value: node.value.substr(0, nb_addable) + "…",
        });
    });

    remove(truncated_tree, (node) => {
        return typeof node.type === "undefined";
    });

    return toHtml(truncated_tree);
}
