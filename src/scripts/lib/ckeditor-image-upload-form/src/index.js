/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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

import french_translations from "../po/fr_FR.po";
import { UploadEnabledDetector } from "./UploadEnabledDetector";
import { initGettextSync } from "@tuleap/gettext";
import { HelpBlockTranslator } from "./HelpBlockTranslator";
import { Initializer } from "./Initializer";

export function initiateUploadImage(ckeditor_instance, options, textarea) {
    const gettext_provider = initGettextSync(
        "rich-text-editor",
        french_translations,
        options.language
    );
    const detector = new UploadEnabledDetector(document, textarea);
    const translator = new HelpBlockTranslator(document, textarea, gettext_provider);
    const initializer = new Initializer(document, gettext_provider, detector, translator);
    initializer.init(ckeditor_instance, textarea);
}

export { getUploadImageOptions } from "./ckeditor-options";
