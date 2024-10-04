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
export interface ToolbarHandler {
    toggleBold: () => void;
    toggleEmbedded: () => void;
    toggleCode: () => void;
    toggleQuote: () => void;
    toggleSubscript: () => void;
    toggleSuperScript: () => void;
}

export interface ToolbarView {
    activateBold: (is_activated: boolean) => void;
    activateEmbedded: (is_activated: boolean) => void;
    activateCode: (is_activated: boolean) => void;
    activateQuote: (is_activated: boolean) => void;
    activateSubscript: (is_activated: boolean) => void;
    activateSuperscript: (is_activated: boolean) => void;
}

export interface ToolbarBus {
    handler: ToolbarHandler | null;
    view: ToolbarView;
    bold: () => void;
    embedded: () => void;
    code: () => void;
    quote: () => void;
    subscript: () => void;
    superscript: () => void;
    setCurrentHandler: (handler: ToolbarHandler) => void;
    setView: (view: Partial<ToolbarView>) => void;
}

const noop = (): void => {
    // Do nothing
};
const default_view: ToolbarView = {
    activateBold: noop,
    activateCode: noop,
    activateQuote: noop,
    activateEmbedded: noop,
    activateSubscript: noop,
    activateSuperscript: noop,
};

export const buildToolbarBus = (): ToolbarBus => ({
    handler: null,
    view: default_view,
    bold(): void {
        this.handler?.toggleBold();
    },
    embedded(): void {
        this.handler?.toggleEmbedded();
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
    setCurrentHandler(handler: ToolbarHandler): void {
        this.handler = handler;
    },
    setView(view: Partial<ToolbarView>): void {
        this.view = {
            ...this.view,
            ...view,
        };
    },
});
