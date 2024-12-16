/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";
import { initGettextSync } from "@tuleap/gettext";
import { UploadEnabledDetector } from "./UploadEnabledDetector";
import { Initializer } from "./Initializer";
import { HelpBlockFactory } from "./HelpBlockFactory";
import { disablePasteOfImages } from "./paste-image-disabler";

export const UploadImageFormFactory = (doc, locale) => {
    const gettext_provider = initGettextSync("rich-text-editor", { fr_FR, pt_BR }, locale);

    return {
        initiateImageUpload(ckeditor_instance, textarea) {
            const detector = new UploadEnabledDetector(doc, textarea);
            const initializer = new Initializer(doc, gettext_provider, detector);
            initializer.init(ckeditor_instance, textarea);
        },

        forbidImageUpload(ckeditor_instance) {
            disablePasteOfImages(ckeditor_instance, gettext_provider);
        },

        createHelpBlock(textarea) {
            const factory = HelpBlockFactory(doc, gettext_provider);
            return factory.createHelpBlock(textarea);
        },
    };
};
