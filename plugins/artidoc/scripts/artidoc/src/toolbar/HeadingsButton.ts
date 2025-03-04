/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { define, html, dispatch } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { createDropdown } from "@tuleap/tlp-dropdown";
import type { StoredArtidocSection } from "@/sections/SectionsCollection";
import type { Level } from "@/sections/levels/SectionsNumberer";
import { LEVEL_1, LEVEL_2, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import type { GetText } from "@tuleap/gettext";
import { getLocaleWithDefault, initGettext } from "@tuleap/gettext";
import { getPOFileFromLocaleWithoutExtension } from "@tuleap/vue3-gettext-init";

export const TAG = "tuleap-prose-mirror-toolbar-headings-button";

export type HeadingsButton = {
    section: StoredArtidocSection | undefined;
    is_disabled: boolean;
    dropdown_menu: HTMLElement;
};

export type InternalHeadingsButton = Readonly<HeadingsButton> & {
    after_render_once: unknown;
    dropdown_trigger: HTMLButtonElement;
    render(): HTMLElement;
};

export type HostElement = InternalHeadingsButton & HTMLElement;

export type UpdateSectionLevelEvent = Event & {
    detail: {
        level: Level;
    };
};

export const isUpdateSectionLevelEvent = (event: Event): event is UpdateSectionLevelEvent => {
    return (
        "detail" in event &&
        typeof event.detail === "object" &&
        event.detail !== null &&
        "level" in event.detail &&
        typeof event.detail.level === "number"
    );
};

export const renderHeadingsButton = (
    host: HostElement,
    gettext_provider: GetText,
): UpdateFunction<InternalHeadingsButton> => {
    const getDropdownItemClasses = (heading_level: Level): Record<string, boolean> => {
        return {
            "artidoc-menuitem-level": true,
            "tlp-dropdown-menu-item": true,
            "artidoc-selected-level": host.section?.level === heading_level,
        };
    };

    const dispatchUpdateSectionLevel = (level: Level): void => {
        dispatch(host, "update-section-level", { detail: { level } });
    };

    return html`
        <button
            class="prose-mirror-button tlp-button-secondary tlp-button-outline"
            disabled="${host.is_disabled}"
            title="${gettext_provider.gettext("Change section heading level")}"
        >
            <i class="fa-solid fa-heading" aria-hidden="true"></i>
            <span id="dropdown-menu-level" class="tlp-dropdown-menu" role="menu">
                <span
                    class="${getDropdownItemClasses(LEVEL_1)}"
                    role="menuitem"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_1)}"
                    title="${gettext_provider.gettext("Change to heading 1")}"
                >
                    <span class="artidoc-heading-icon">H1</span>
                    ${gettext_provider.gettext("Heading 1")}
                </span>
                <span
                    class="${getDropdownItemClasses(LEVEL_2)}"
                    role="menuitem"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_2)}"
                    title="${gettext_provider.gettext("Change to heading 2")}"
                >
                    <span class="artidoc-heading-icon">H2</span>
                    ${gettext_provider.gettext("Heading 2")}
                </span>
                <span
                    class="${getDropdownItemClasses(LEVEL_3)}"
                    role="menuitem"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_3)}"
                    title="${gettext_provider.gettext("Change to heading 3")}"
                >
                    <span class="artidoc-heading-icon">H3</span>
                    ${gettext_provider.gettext("Heading 3")}
                </span>
            </span>
        </button>
    `;
};

const after_render_once_descriptor = {
    value: (host: InternalHeadingsButton): unknown => host.render(),
    observe(host: HostElement): void {
        createDropdown(host.dropdown_trigger, {
            dropdown_menu: host.dropdown_menu,
            trigger: "click",
            keyboard: true,
        });
    },
};

initGettext(
    getLocaleWithDefault(document),
    "tlp-prose-mirror-toolbar",
    (locale) => import(`../../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
).then((gettext_provider) => {
    define<InternalHeadingsButton>({
        tag: TAG,
        is_disabled: true,
        section: undefined,
        after_render_once: after_render_once_descriptor,
        dropdown_trigger: {
            value: (host: HostElement): HTMLButtonElement => {
                const button = host.render().querySelector("button");
                if (!(button instanceof HTMLButtonElement)) {
                    throw new Error("Unable to find the dropdown trigger in the headings button");
                }
                return button;
            },
        },
        dropdown_menu: {
            value: (host: HostElement): HTMLElement => {
                const menu = host.render().querySelector("[role=menu]");
                if (!(menu instanceof HTMLElement)) {
                    throw new Error("Unable to find the dropdown menu in the headings button");
                }
                return menu;
            },
        },
        render: (host) => renderHeadingsButton(host, gettext_provider),
    });
});
