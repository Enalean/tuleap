/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type {
    ToolbarBus,
    LinkProperties,
    ImageProperties,
    EmojiProperties,
} from "@tuleap/prose-mirror-editor-toolbar";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor-toolbar";

const default_toolbar_state = {
    is_bold_active: false,
    is_italic_active: false,
    is_code_active: false,
    is_quote_active: false,
    is_ordered_list_active: false,
    is_bullet_list_active: false,
    is_subscript_active: false,
    is_superscript_active: false,
    is_link_active: false,
    is_unlink_active: false,
    is_image_active: false,
    is_emoji_active: false,
};

let toolbar_state = { ...default_toolbar_state };

export const getToolbarDemoBus = (): ToolbarBus => {
    const toolbar_bus = buildToolbarBus();

    const noop = (): void => {
        // Do nothing;
    };

    toolbar_bus.setCurrentHandler({
        toggleBold(): void {
            toolbar_state.is_bold_active = !toolbar_state.is_bold_active;
            toolbar_bus.view.activateBold(toolbar_state.is_bold_active);
        },
        toggleItalic(): void {
            toolbar_state.is_italic_active = !toolbar_state.is_italic_active;
            toolbar_bus.view.activateItalic(toolbar_state.is_italic_active);
        },
        toggleCode(): void {
            toolbar_state.is_code_active = !toolbar_state.is_code_active;
            toolbar_bus.view.activateCode(toolbar_state.is_code_active);
        },
        toggleQuote(): void {
            toolbar_state.is_quote_active = !toolbar_state.is_quote_active;
            toolbar_bus.view.activateQuote(toolbar_state.is_quote_active);
        },
        toggleSubscript(): void {
            toolbar_state.is_subscript_active = !toolbar_state.is_subscript_active;
            toolbar_bus.view.activateSubscript(toolbar_state.is_subscript_active);
        },
        toggleSuperScript(): void {
            toolbar_state.is_superscript_active = !toolbar_state.is_superscript_active;
            toolbar_bus.view.activateSuperscript(toolbar_state.is_superscript_active);
        },
        applyLink(link: LinkProperties): void {
            toolbar_state.is_link_active = link.href.trim() !== "";
            toolbar_state.is_unlink_active = toolbar_state.is_link_active;

            toolbar_bus.view.activateUnlink(toolbar_state.is_link_active);
            toolbar_bus.view.activateLink({
                is_activated: toolbar_state.is_link_active,
                is_disabled: false,
                link_title: "See example here",
                link_href: "https://example.com",
            });
        },
        applyUnlink(): void {
            toolbar_state.is_unlink_active = false;
            toolbar_state.is_link_active = false;

            toolbar_bus.view.activateUnlink(toolbar_state.is_unlink_active);
            toolbar_bus.view.activateLink({
                is_activated: false,
                is_disabled: false,
                link_title: "See example here",
                link_href: "https://example.com",
            });
        },
        applyImage(image: ImageProperties): void {
            toolbar_state.is_image_active = image.src.trim() !== "";
            toolbar_bus.view.activateImage({
                is_activated: true,
                is_disabled: false,
                image_src: "https://example.com/example.jpg",
                image_title: "An example image",
            });
        },
        applyEmoji(emoji: EmojiProperties): void {
            toolbar_state.is_emoji_active = emoji.emoji !== "";
            toolbar_bus.view.activateEmoji({
                is_activated: true,
                is_disabled: false,
                emoji_string: emoji.emoji,
            });
        },
        toggleOrderedList(): void {
            const is_ordered_list_active = !toolbar_state.is_ordered_list_active;

            toolbar_state.is_ordered_list_active = is_ordered_list_active;

            toolbar_bus.view.activateOrderedList({
                is_activated: is_ordered_list_active,
                is_disabled: false,
            });
            toolbar_bus.view.activateBulletList({
                is_activated: false,
                is_disabled: is_ordered_list_active,
            });
        },
        toggleBulletList(): void {
            const is_bullet_list_active = !toolbar_state.is_bullet_list_active;

            toolbar_state.is_bullet_list_active = is_bullet_list_active;

            toolbar_bus.view.activateBulletList({
                is_activated: is_bullet_list_active,
                is_disabled: false,
            });
            toolbar_bus.view.activateOrderedList({
                is_activated: false,
                is_disabled: is_bullet_list_active,
            });
        },
        toggleHeading: noop,
        togglePlainText: noop,
        togglePreformattedText: noop,
        toggleSubtitle: noop,
        focus: noop,
    });

    return toolbar_bus;
};

export const initToolbarDemo = (toolbar_bus: ToolbarBus, is_toolbar_disabled: boolean): void => {
    setTimeout(() => {
        if (is_toolbar_disabled) {
            toolbar_state = { ...default_toolbar_state };
            toolbar_bus.disableToolbar();
            return;
        }

        toolbar_bus.enableToolbar();

        toolbar_bus.view.activateBold(toolbar_state.is_bold_active);
        toolbar_bus.view.activateItalic(toolbar_state.is_italic_active);
        toolbar_bus.view.activateCode(toolbar_state.is_code_active);
        toolbar_bus.view.activateQuote(toolbar_state.is_quote_active);
        toolbar_bus.view.activateSubscript(toolbar_state.is_subscript_active);
        toolbar_bus.view.activateSuperscript(toolbar_state.is_superscript_active);

        toolbar_bus.view.activateOrderedList({
            is_activated: toolbar_state.is_ordered_list_active,
            is_disabled: toolbar_state.is_bullet_list_active,
        });

        toolbar_bus.view.activateBulletList({
            is_activated: toolbar_state.is_bullet_list_active,
            is_disabled: toolbar_state.is_ordered_list_active,
        });

        toolbar_bus.view.activateUnlink(toolbar_state.is_unlink_active);
        toolbar_bus.view.activateLink({
            is_activated: toolbar_state.is_link_active,
            is_disabled: false,
            link_title: "See example here",
            link_href: "https://example.com",
        });

        toolbar_bus.view.activateImage({
            is_activated: toolbar_state.is_image_active,
            is_disabled: false,
            image_src: "https://example.com/example.jpg",
            image_title: "An example image",
        });
    });
};
