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

import { findAllHiddenInputByNames } from "./form-adapter.js";
import { findImageUrls } from "./image-urls-finder.js";

const forms = new Map();

export function addInstance(form, ckeditor_instance, field_name) {
    if (!forms.has(form)) {
        forms.set(form, []);
        form.addEventListener("submit", removeUnusedUploadedFilesFromForm);
    }

    forms.set(form, forms.get(form).concat({ ckeditor_instance, field_name }));
}

function removeUnusedUploadedFilesFromForm() {
    const form = this;
    const instances = forms.get(form);
    const used_urls = getUsedUploadedImagesURLsInAllCKEditorInstances(instances);

    for (const input of getPotentiallyUsedUploadedFiles(form, instances)) {
        if (
            typeof input.dataset.url === "undefined" ||
            used_urls.find((used_url) => used_url === input.dataset.url)
        ) {
            continue;
        }

        input.parentNode.removeChild(input);
    }
}

function getUsedUploadedImagesURLsInAllCKEditorInstances(instances) {
    return instances.flatMap(({ ckeditor_instance }) => findImageUrls(ckeditor_instance.getData()));
}

function getPotentiallyUsedUploadedFiles(form, instances) {
    const field_names = instances.map(({ field_name }) => field_name);
    return findAllHiddenInputByNames(form, field_names);
}
