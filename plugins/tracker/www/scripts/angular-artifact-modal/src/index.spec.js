/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import "tlp-mocks";
import "ckeditor-mocks";

/**
 * Some AngularJS tests load "tuleap-artifact-modal.js". It in turns initializes
 * vue-gettext with the language from the <body> tag.
 * As long as those things depend on the modal's root module, we're stuck with this.
 * Otherwise, Vue tests fail with:
 * Error in render: "TypeError: Cannot read property 'split' of undefined"
 */
beforeAll(() => {
    document.body.dataset.userLocale = "en_US";
});

afterAll(() => {
    document.body.dataset.userLocale = undefined;
});
import "./tuleap-artifact-modal-controller.spec.js";
import "./tuleap-artifact-modal-service.spec.js";
import "./field-dependencies-service.spec.js";
import "./validate-service.spec.js";
// end of annoying AngularJS tests

import "./common/FormatSelector.spec.js";
import "./common/RichTextEditor.spec.js";
import "./followups/FollowupEditor.spec.js";
import "./model/field-values-service.spec.js";
import "./model/form-tree-builder.spec.js";
import "./model/tracker-transformer-service.spec.js";
import "./model/workflow-field-values-filter.spec.js";
import "./rest/rest-service.spec.js";
import "./tuleap-artifact-modal-fields/computed-field/computed-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/computed-field/computed-field-value-formatter.spec.js";
import "./tuleap-artifact-modal-fields/disabled-field-detector.spec.js";
import "./tuleap-artifact-modal-fields/file-field/file-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/file-field/file-field-detector.spec.js";
import "./tuleap-artifact-modal-fields/file-field/file-field-validator.spec.js";
import "./tuleap-artifact-modal-fields/file-field/file-upload-rules-state.spec.js";
import "./tuleap-artifact-modal-fields/link-field/link-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/link-field/link-field-service.spec.js";
import "./tuleap-artifact-modal-fields/link-field/link-field-value-formatter.spec.js";
import "./tuleap-artifact-modal-fields/open-list-field/open-list-field-validate-service.spec.js";
import "./tuleap-artifact-modal-fields/open-list-field/static-open-list-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/open-list-field/ugroups-open-list-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/open-list-field/users-open-list-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/permission-field/permission-field-controller.spec.js";
import "./tuleap-artifact-modal-fields/permission-field/permission-field-value-formatter.spec.js";
import "./tuleap-artifact-modal-fields/text-field/TextField.spec.js";
import "./tuleap-highlight/highlight-directive.spec.js";
