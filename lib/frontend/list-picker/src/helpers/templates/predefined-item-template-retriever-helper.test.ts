/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, it, expect } from "vitest";
import { html, render } from "lit-html";
import { convertBadColorHexToRGB } from "../color-helper";
import { retrievePredefinedTemplate } from "./predefined-item-template-retriever-helper";
import { styleMap } from "lit-html/directives/style-map.js";

describe("predefined-item-template-retriever", () => {
    it("should return the avatar template if data-avatar-url is set", () => {
        const option_with_image = document.createElement("option");
        const name = "John Doe (jdoe)";
        option_with_image.appendChild(document.createTextNode(name));
        const avatar_url = "/url/to/jdoe/avatar.png";
        option_with_image.setAttribute("data-avatar-url", avatar_url);

        // The source of truth is the production code, Prettier should not interfere with the spacing here
        // prettier-ignore
        expect(retrievePredefinedTemplate(option_with_image)).toEqual(
            html`
            <span class="list-picker-avatar"><img src="${avatar_url}" loading="lazy" /></span>
            ${name}
        `
        );
    });

    it("should return the colored template according to the color given from a color picker", () => {
        const option_with_colored_badge = document.createElement("option");
        const label = "TS050 Hybrid";
        option_with_colored_badge.appendChild(document.createTextNode(label));
        const color = "fiesta-red";
        option_with_colored_badge.setAttribute("data-color-value", color);

        const div = document.createElement("div");
        render(retrievePredefinedTemplate(option_with_colored_badge), div);
        expect(stripExpressionComments(div.innerHTML)).toMatchInlineSnapshot(`
          "
                      <span class="list-picker-option-colored-label-container">
                          <span class="tlp-swatch-fiesta-red list-picker-circular-color"></span>
                          TS050 Hybrid
                      </span>
                  "
        `);
    });

    it("should return the legacy colored template if the color is an old color", () => {
        const option_with_colored_badge = document.createElement("option");
        const label = "GT-R LM Nismo";
        option_with_colored_badge.appendChild(document.createTextNode(label));
        const legacy_color = "#ff0000";
        option_with_colored_badge.setAttribute("data-color-value", legacy_color);

        const rgb_legacy_color = convertBadColorHexToRGB(legacy_color);

        if (rgb_legacy_color === null) {
            throw new Error("rgb_legacy_color should not be null");
        }

        const legacy_color_styles = {
            background: `rgba(${rgb_legacy_color.red}, ${rgb_legacy_color.green}, ${rgb_legacy_color.blue}, .6)`,
            border: `3px solid rgba(${rgb_legacy_color.red}, ${rgb_legacy_color.green}, ${rgb_legacy_color.blue})`,
            color: `${legacy_color}`,
        };

        // The source of truth is the production code, Prettier should not interfere with the spacing here
        //prettier-ignore
        expect(retrievePredefinedTemplate(option_with_colored_badge)).toEqual(
            html`
            <span class="list-picker-option-colored-label-container">
                <span
                    class="list-picker-circular-legacy-color"
                    style="${styleMap(legacy_color_styles)}"
                ></span>
                ${label}
            </span>
        `);
    });
});

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
