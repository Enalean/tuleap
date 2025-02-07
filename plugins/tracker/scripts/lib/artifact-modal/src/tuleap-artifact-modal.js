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

import angular from "angular";
import "angular-gettext";
import angular_tlp from "@tuleap/angular-tlp";
import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";

import angular_custom_elements_module from "angular-custom-elements";

import { STRUCTURAL_FIELDS } from "@tuleap/plugin-tracker-constants";
import { setCatalog } from "./gettext-catalog";

import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";
import UsersOpenListFieldDirective from "./fields/open-list-field/users-open-list-field-directive.js";
import AwkwardCreationFields from "./model/awkward-creation-fields-constant.js";

import "./adapters/UI/fields/computed-field/ComputedField";
import "./adapters/UI/fields/float-field/FloatField";
import "./adapters/UI/fields/int-field/IntField";
import "./adapters/UI/fields/radio-buttons-field/RadioButtonsField";
import "./adapters/UI/fields/string-field/StringField";
import "./adapters/UI/fields/text-field/TextField";
import "./adapters/UI/fields/date-field/DateField";
import "./adapters/UI/fields/burndown-field/BurndownField";
import "./adapters/UI/fields/artifact-id-field/ArtifactIdField";
import "./adapters/UI/fields/priority-field/PriorityField";
import "./adapters/UI/fields/cross-references-field/CrossReferencesField";
import "./adapters/UI/fields/user-avatar-field/UserAvatarField";
import "./adapters/UI/fields/date-readonly-field/DateReadonlyField";
import "./adapters/UI/fields/file-field/FileField";
import "./adapters/UI/fields/permission-field/PermissionField";
import "./adapters/UI/fields/checkbox-field/CheckboxField";
import "./adapters/UI/fields/select-box-field/SelectBoxField";
import "./adapters/UI/fields/open-list-field/static/StaticOpenListField";
import "./adapters/UI/fields/open-list-field/user-groups/UserGroupOpenListField";
import "./adapters/UI/feedback/ModalFeedback";
import "./adapters/UI/footer/FileUploadQuota";
import "./adapters/UI/comments/ModalCommentsSection";

export default angular
    .module("tuleap.artifact-modal", ["gettext", angular_tlp, angular_custom_elements_module])
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            [fr_FR, pt_BR].forEach((translations_catalog) => {
                for (const [language, strings] of Object.entries(translations_catalog)) {
                    const short_language = language.split("_")[0];
                    gettextCatalog.setStrings(short_language, strings);
                    setCatalog(gettextCatalog);
                }
            });
        },
    ])
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .controller("TuleapArtifactModalController", ArtifactModalController)
    .directive("tuleapArtifactModalUsersOpenListField", UsersOpenListFieldDirective)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    }).name;
