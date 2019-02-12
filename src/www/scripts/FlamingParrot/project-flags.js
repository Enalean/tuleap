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
    const project_flags_element = document.querySelector(".current-project-nav-flag");
    if (!project_flags_element) {
        return;
    }

    const template = render(
        `<div class="popover current-project-nav-flag-popover">
                <div class="arrow current-project-nav-flag-popover-arrow"></div>
                <h3>{{ title }}</h3>
                <div class="popover-content"></div>
        </div>`,
        { title: project_flags_element.dataset.title }
    );

    const content = render(
        `<p>{{ description }}</p>
        <ul class="current-project-nav-flag-popover-flags">
            {{# project_flags }}
                <li class="current-project-nav-flag-popover-flag">
                    <h2 class="current-project-nav-flag-popover-title">
                        {{ label }}
                    </h2>
                    <p class="current-project-nav-flag-popover-description">
                        {{ description }}
                    </p>
                </li>
            {{/ project_flags }}
        </ul>`,
        {
            project_flags: JSON.parse(project_flags_element.dataset.projectFlags),
            description: project_flags_element.dataset.description
        }
    );

    $(project_flags_element).popover({
        title: project_flags_element.dataset.title,
        placement: "bottom",
        html: true,
        trigger: "hover",
        template,
        content
    });
});
