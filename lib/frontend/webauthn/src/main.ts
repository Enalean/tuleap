/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";
import { getTargetModal, openTargetModalIdOnClick } from "@tuleap/tlp-modal";
import { selectOrThrow } from "@tuleap/dom";
import { authenticate, canUserDoWebAuthn, getAuthenticationResult } from "./authenticate";
import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";
import { initGettextSync } from "@tuleap/gettext";
import type { Fault } from "@tuleap/fault";
import { AUTHENTICATION_MODAL_TAG, AuthenticationModal } from "./AuthenticationModal";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { en_US_LOCALE } from "@tuleap/core-constants";

export { authenticate, getAuthenticationResult, AUTHENTICATION_MODAL_TAG };

const isUserHasNoRegisteredPasskey = (fault: Fault): boolean =>
    "isUserHasNoRegisteredPasskey" in fault && fault.isUserHasNoRegisteredPasskey() === true;

/**
 * Open an authentication modal before opening target modal
 */
export function openTargetModalIdAfterAuthentication(
    doc: Document,
    button_id: string
): ResultAsync<Modal | null, Fault> {
    const button = selectOrThrow(doc, `#${button_id}`, HTMLButtonElement);
    button.disabled = true;

    return canUserDoWebAuthn()
        .map((): Modal | null => {
            button.disabled = false;

            const gettext_provider = initGettextSync(
                "tuleap-webauthn",
                { fr_FR, pt_BR },
                doc.body.dataset.userLocale ?? en_US_LOCALE
            );

            const target_modal = getTargetModal(doc, button);

            const auth_modal = doc.createElement(AUTHENTICATION_MODAL_TAG);
            if (!(auth_modal instanceof AuthenticationModal)) {
                throw new Error("Created auth modal is not an AuthenticationModal");
            }
            auth_modal.setTargetModal(target_modal);
            auth_modal.setGettextProvider(gettext_provider);
            doc.body.appendChild(auth_modal);

            button.addEventListener("click", () => {
                auth_modal.show();
            });

            return target_modal;
        })
        .orElse((fault) => {
            button.disabled = false;

            if (isUserHasNoRegisteredPasskey(fault)) {
                return okAsync(openTargetModalIdOnClick(doc, button_id));
            }
            return errAsync(fault);
        });
}
