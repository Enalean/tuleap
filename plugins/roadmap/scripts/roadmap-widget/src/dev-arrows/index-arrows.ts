/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import {
    getDownLeftArrow,
    getDownRightArrow,
    getUpLeftArrow,
    getUpRightArrow,
} from "../helpers/svg-arrow-path";
import { gap } from "../helpers/path";
import type { SVGTemplateResult } from "lit/html.js";
import { html, svg, render } from "lit/html.js";

function getExampleSvg(
    width: number,
    height: number,
    color: string,
    callback: (width: number, height: number) => string,
): SVGTemplateResult {
    return svg`
        <svg style="height: ${height + 2 * gap}px; width: ${width + 2 * gap}px">
            <path
                stroke="dimgray"
                stroke-width="1"
                fill="none"
                shape-rendering="crispEdges"
                d="M0 0
                    L${width + 2 * gap - 1} 0
                    L${width + 2 * gap - 1} ${height + 2 * gap - 1}
                    L0 ${height + 2 * gap - 1}Z"
            />
            <path
                stroke="silver"
                stroke-width="1"
                stroke-dasharray="2"
                fill="none"
                shape-rendering="crispEdges"
                d="M${gap} ${gap}
                    L${width + 2 * gap - gap} ${gap}
                    L${width + 2 * gap - gap} ${height + 2 * gap - gap}
                    L${gap} ${height + 2 * gap - gap}Z"
            />
            <path
                stroke="${color}"
                stroke-width="1.5px"
                stroke-linejoin="round"
                stroke-linecap="round"
                fill="none"
                d="${callback(width + 2 * gap, height + 2 * gap)}"
            />
        </svg>
    `;
}

const height = 60;
const max_width = 60;
const step = 2;

const theme_color = "#1593c4";
const danger_color = "#da5353";

const content = [
    { title: "Down right arrows", color: theme_color, callback: getDownRightArrow },
    { title: "Down left arrows", color: danger_color, callback: getDownLeftArrow },
    { title: "Up right arrows", color: theme_color, callback: getUpRightArrow },
    { title: "Up left arrows", color: danger_color, callback: getUpLeftArrow },
].reduce(
    (previous, { title, color, callback }) => {
        let content = html`
            ${previous}
            <h1>${title}</h1>
        `;
        for (let width = 0; width < max_width; width += step) {
            content = html`${content}${getExampleSvg(width, height, color, callback)}`;
        }

        return content;
    },
    html``,
);

render(content, document.body);
