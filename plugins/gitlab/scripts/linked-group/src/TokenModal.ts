/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { selectOrThrow } from "@tuleap/dom";
import { createModal } from "@tuleap/tlp-modal";

export const EDIT_TOKEN_BUTTON_SELECTOR = "#edit-token-button";
export const TOKEN_MODAL_SELECTOR = "#token-modal";

export const EDIT_ICON_CLASSNAME = "fa-arrow-right";

type TokenModalType = {
    init(): void;
};

export const TokenModal = (doc: Document): TokenModalType => {
    const edit_button = selectOrThrow(doc, EDIT_TOKEN_BUTTON_SELECTOR, HTMLButtonElement);
    const token_modal = selectOrThrow(doc, TOKEN_MODAL_SELECTOR);

    const modal_instance = createModal(token_modal);

    const onClickEdit = (): void => {
        modal_instance.show();
    };

    return {
        init(): void {
            edit_button.addEventListener("click", onClickEdit);
        },
    };
};
