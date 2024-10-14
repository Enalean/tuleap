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

import { define, html, type UpdateFunction } from "hybrids";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { getClass } from "../../helpers/class-getter";
import type { GetText } from "@tuleap/gettext";

export const QUOTE_TAG_NAME = "quote-item";

export type QuoteElement = {
    toolbar_bus: ToolbarBus;
    gettext_provider: GetText;
};

type InternalQuoteElement = Readonly<QuoteElement> & {
    is_activated: boolean;
};

export type HostElement = InternalQuoteElement & HTMLElement;
const onClickApplyQuote = (host: QuoteElement): void => {
    host.toolbar_bus.quote();
};
export const renderQuoteItem = (
    host: InternalQuoteElement,
    gettext_provider: GetText,
): UpdateFunction<InternalQuoteElement> => {
    const classes = getClass(host.is_activated);

    return html`<button
        class="${classes}"
        onclick="${onClickApplyQuote}"
        data-test="button-quote"
        title="${gettext_provider.gettext("Wrap in block quote `Ctrl->`")}"
    >
        <i class="fa-solid fa-quote-left" role="img"></i>
    </button>`;
};

export const connect = (host: InternalQuoteElement): void => {
    host.toolbar_bus.setView({
        activateQuote: (is_activated: boolean): void => {
            host.is_activated = is_activated;
        },
    });
};

export default define<InternalQuoteElement>({
    tag: QUOTE_TAG_NAME,
    is_activated: false,
    toolbar_bus: {
        value: (host: QuoteElement, toolbar_bus: ToolbarBus) => toolbar_bus,
        connect,
    },
    gettext_provider: (host, gettext_provider) => gettext_provider,
    render: (host) => renderQuoteItem(host, host.gettext_provider),
});
