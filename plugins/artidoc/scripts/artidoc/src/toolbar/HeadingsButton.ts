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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import type { StoredArtidocSection } from "@/sections/SectionsCollection";
import type { Level } from "@/sections/levels/SectionsNumberer";
import { LEVEL_1, LEVEL_2, LEVEL_3 } from "@/sections/levels/SectionsNumberer";
import type { GetText } from "@tuleap/gettext";
import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";

export const TAG = "tuleap-prose-mirror-toolbar-headings-button";

export type HeadingsButton = {
    section: StoredArtidocSection | undefined;
};

export type InternalHeadingsButton = Readonly<HeadingsButton> & {
    is_button_disabled: boolean;
    dropdown_trigger: HTMLButtonElement;
    dropdown_menu: HTMLElement;
    dropdown_instance: Dropdown | undefined;
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
    const isLevelDisabled = (level: Level): boolean => host.section?.level === level;

    const dispatchUpdateSectionLevel = (level: Level): void => {
        dispatch(host, "update-section-level", { detail: { level } });

        host.dropdown_instance?.hide();
    };

    return html`
        <div class="tlp-dropdown">
            <button
                class="prose-mirror-button tlp-button-secondary tlp-button-outline"
                disabled="${host.is_button_disabled}"
                title="${gettext_provider.gettext("Change section heading level")}"
                data-test="change-section-level"
                type="button"
            >
                <i class="fa-solid fa-heading" aria-hidden="true"></i>
            </button>
            <div class="tlp-dropdown-menu" role="menu">
                <button
                    role="menuitem"
                    type="button"
                    class="tlp-dropdown-menu-item headings-button-dropdown-option"
                    disabled="${isLevelDisabled(LEVEL_1)}"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_1)}"
                    title="${gettext_provider.gettext("Change to heading 1")}"
                    data-test="change-section-level-1"
                >
                    <span class="artidoc-heading-icon">H1</span>
                    ${gettext_provider.gettext("Heading 1")}
                </button>
                <button
                    role="menuitem"
                    type="button"
                    class="tlp-dropdown-menu-item headings-button-dropdown-option"
                    disabled="${isLevelDisabled(LEVEL_2)}"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_2)}"
                    title="${gettext_provider.gettext("Change to heading 2")}"
                    data-test="change-section-level-2"
                >
                    <span class="artidoc-heading-icon">H2</span>
                    ${gettext_provider.gettext("Heading 2")}
                </button>
                <button
                    role="menuitem"
                    type="button"
                    class="tlp-dropdown-menu-item headings-button-dropdown-option"
                    disabled="${isLevelDisabled(LEVEL_3)}"
                    onclick="${(): void => dispatchUpdateSectionLevel(LEVEL_3)}"
                    title="${gettext_provider.gettext("Change to heading 3")}"
                    data-test="change-section-level-3"
                >
                    <span class="artidoc-heading-icon">H3</span>
                    ${gettext_provider.gettext("Heading 3")}
                </button>
            </div>
        </div>
    `;
};

initGettext(
    getLocaleWithDefault(document),
    "tlp-prose-mirror-toolbar",
    (locale) => import(`../../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
).then((gettext_provider) => {
    define<InternalHeadingsButton>({
        tag: TAG,
        is_button_disabled: (host: HeadingsButton) => !host.section,
        section: undefined,
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
        dropdown_instance: {
            value: undefined,
            connect(host): () => void {
                const dropdown_instance = createDropdown(host.dropdown_trigger, {
                    dropdown_menu: host.dropdown_menu,
                    trigger: "click",
                    keyboard: true,
                });
                host.dropdown_instance = dropdown_instance;

                return () => {
                    dropdown_instance.destroy();
                };
            },
        },
        render: (host) => renderHeadingsButton(host, gettext_provider),
    });
});
