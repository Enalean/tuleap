/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { LinkState } from "../links/LinkState";
import type { ImageState } from "../image/ImageState";
import type { ImageProperties, LinkProperties, EmojiProperties } from "../../../types";
import type { ListState } from "../list/ListState";
import type { Heading } from "../text-style/Heading";
import type { EmojiState } from "../emoji/EmojiState";

export interface ToolbarHandler {
    toggleBold: () => void;
    toggleItalic: () => void;
    toggleCode: () => void;
    toggleQuote: () => void;
    toggleSubscript: () => void;
    toggleSuperScript: () => void;
    toggleHeading: (heading: Heading) => void;
    toggleSubtitle: () => void;
    togglePlainText: () => void;
    togglePreformattedText: () => void;
    applyLink: (link: LinkProperties) => void;
    applyUnlink: () => void;
    applyImage: (image: ImageProperties) => void;
    toggleOrderedList: () => void;
    toggleBulletList: () => void;
    applyEmoji: (emoji: EmojiProperties) => void;
    focus: () => void;
}

export interface ToolbarView {
    activateBold: (is_activated: boolean) => void;
    activateItalic: (is_activated: boolean) => void;
    activateCode: (is_activated: boolean) => void;
    activateQuote: (is_activated: boolean) => void;
    activateSubscript: (is_activated: boolean) => void;
    activateSuperscript: (is_activated: boolean) => void;
    activateLink: (link_state: LinkState) => void;
    activateUnlink: (is_activated: boolean) => void;
    activateImage: (image_state: ImageState) => void;
    activateOrderedList: (list_state: ListState) => void;
    activateBulletList: (list_state: ListState) => void;
    activateHeading: (current_heading: Heading | null) => void;
    activatePlainText: (is_activated: boolean) => void;
    activatePreformattedText: (is_activated: boolean) => void;
    activateSubtitle: (is_activated: boolean) => void;
    activateEmoji: (emoji_state: EmojiState) => void;
    toggleToolbarState: (is_enabled: boolean) => void;
    toggleToolbarMenu: (menu: string) => void;
}

export interface ToolbarBus {
    handler: ToolbarHandler | null;
    view: ToolbarView;
    bold: () => void;
    italic: () => void;
    code: () => void;
    quote: () => void;
    subscript: () => void;
    superscript: () => void;
    link: (link: LinkProperties) => void;
    unlink: () => void;
    image: (image: ImageProperties) => void;
    orderedList: () => void;
    bulletList: () => void;
    heading: (heading: Heading) => void;
    subtitle: () => void;
    plainText: () => void;
    preformattedText: () => void;
    setCurrentHandler: (handler: ToolbarHandler) => void;
    setView: (view: Partial<ToolbarView>) => void;
    disableToolbar: () => void;
    enableToolbar: () => void;
    toggleMenu: (menu: string) => void;
    emoji: (emoji: EmojiProperties) => void;
    focusEditor: () => void;
}

const noop = (): void => {
    // Do nothing
};
const default_view: ToolbarView = {
    activateBold: noop,
    activateCode: noop,
    activateQuote: noop,
    activateItalic: noop,
    activateSubscript: noop,
    activateSuperscript: noop,
    activateLink: noop,
    activateUnlink: noop,
    activateImage: noop,
    activateOrderedList: noop,
    activateBulletList: noop,
    activateHeading: noop,
    activatePlainText: noop,
    activatePreformattedText: noop,
    activateSubtitle: noop,
    activateEmoji: noop,
    toggleToolbarState: noop,
    toggleToolbarMenu: noop,
};

export const buildToolbarBus = (): ToolbarBus => ({
    handler: null,
    view: default_view,
    bold(): void {
        this.handler?.toggleBold();
    },
    italic(): void {
        this.handler?.toggleItalic();
    },
    code(): void {
        this.handler?.toggleCode();
    },
    quote(): void {
        this.handler?.toggleQuote();
    },
    subscript(): void {
        this.handler?.toggleSubscript();
    },
    superscript(): void {
        this.handler?.toggleSuperScript();
    },
    link(link: LinkProperties): void {
        this.handler?.applyLink(link);
    },
    unlink(): void {
        this.handler?.applyUnlink();
    },
    image(image: ImageProperties): void {
        this.handler?.applyImage(image);
    },
    orderedList(): void {
        this.handler?.toggleOrderedList();
    },
    bulletList(): void {
        this.handler?.toggleBulletList();
    },
    heading(heading: Heading): void {
        this.handler?.toggleHeading(heading);
    },
    subtitle(): void {
        this.handler?.toggleSubtitle();
    },
    plainText(): void {
        this.handler?.togglePlainText();
    },
    preformattedText(): void {
        this.handler?.togglePreformattedText();
    },
    setCurrentHandler(handler: ToolbarHandler): void {
        this.handler = handler;
    },
    setView(view: Partial<ToolbarView>): void {
        this.view = {
            ...this.view,
            ...view,
        };
    },
    disableToolbar(): void {
        this.view.toggleToolbarState(false);
        this.handler = null;
    },
    enableToolbar(): void {
        this.view.toggleToolbarState(true);
    },
    emoji(emoji: EmojiProperties): void {
        this.handler?.applyEmoji(emoji);
    },
    toggleMenu(menu: string): void {
        this.view.toggleToolbarMenu(menu);
    },
    focusEditor(): void {
        this.handler?.focus();
    },
});
