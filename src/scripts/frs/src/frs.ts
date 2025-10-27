/*
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

import "./frs.scss";
import { instantiateUserAutocompleter } from "./instantiate-user-autocompleter";
import { getLocaleWithDefault } from "@tuleap/locale";
import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { instantiateDatePicker } from "./instantiate-date-picker";
import { initiateLinks } from "./initiate-links";
import { initiateReleasenoteSwitch } from "./inititate-releasenote-switch";
import { initiateLicenseAgreementModals } from "./intiate-license-agreement-modals";
import { initiateScroll } from "./initiate-scroll";
import { initiateFileUpload } from "./initiate-file-upload";
import { initiatePermissionsChange } from "./initiate-permissions-change";
import { initiateCheckBeforeSubmit } from "./initiate-check-before-submit";

document.addEventListener("DOMContentLoaded", () => {
    const locale = getLocaleWithDefault(document);

    instantiateUserAutocompleter(locale);
    instantiateDatePicker("frs-release-date-picker", locale);
    initiateLinks();
    initiateReleasenoteSwitch();
    initiateLicenseAgreementModals();
    initiateScroll();
    initiateFileUpload();
    initiatePermissionsChange();
    initiateCheckBeforeSubmit();
    openAllTargetModalsOnClick(document, ".frs-modal-trigger-button");
});
