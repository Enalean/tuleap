/**
 * Copyright (c) 2019, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import $ from "jquery";
import { render } from "mustache";

document.addEventListener("DOMContentLoaded", () => {
    const project_privacy_element = document.getElementById("breadcrumb-project-privacy-icon");
    if (!project_privacy_element) {
        return;
    }

    const template = render(
        `<div class="popover current-project-nav-popover current-project-nav-popover-privacy">
                <div class="arrow"></div>
                <h3>{{ title }}</h3>
                <div class="popover-content"></div>
        </div>`,
        { title: project_privacy_element.title }
    );

    const svg = project_privacy_element.querySelector(".tuleap-svg-project-shield");
    const shield_icon_html = svg ? new XMLSerializer().serializeToString(svg) : "";

    const project_flags = JSON.parse(project_privacy_element.dataset.projectFlags);

    const content = render(
        `<p class="current-project-nav-flag-popover-content-description">{{ content }}</p>
            {{# has_project_flags }}
                <hr class="current-project-nav-flag-popover-separator">
                {{# project_flags }}
                    <div class="current-project-nav-flag-popover-flag">
                        ${shield_icon_html}
                        <h2 class="current-project-nav-flag-popover-content-title">
                            {{ label }}
                        </h2>
                        {{# description }}
                            <p class="current-project-nav-flag-popover-content-description">
                                {{ description }}
                            </p>
                        {{/ description }}
                    </div>
                {{/ project_flags }}
            {{/ has_project_flags }}
            `,
        {
            content: project_privacy_element.dataset.content,
            project_flags,
            has_project_flags: project_flags.length > 0,
        }
    );

    $(project_privacy_element).popover({
        placement: "bottom",
        trigger: "hover",
        html: true,
        template,
        content,
    });
});
