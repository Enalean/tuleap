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
import ngSanitize from "angular-sanitize";
import angular_moment from "angular-moment";
import filter from "angular-filter";
import "angular-gettext";
import angular_tlp from "@tuleap/angular-tlp";
import french_translations from "../po/fr_FR.po";

import angular_custom_elements_module from "angular-custom-elements";

import "../../../../../../src/scripts/tuleap/custom-elements/relative-date";
import { STRUCTURAL_FIELDS } from "../../../constants/fields-constants.js";
import { setCatalog } from "./gettext-catalog";

import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";
import FileFieldDirective from "./fields/file-field/file-field-directive.js";
import FileInputDirective from "./fields/file-field/file-input-directive.js";
import LinkFieldDirective from "./fields/link-field/link-field-directive.js";
import StaticOpenListFieldDirective from "./fields/open-list-field/static-open-list-field-directive.js";
import UgroupsOpenListFieldDirective from "./fields/open-list-field/ugroups-open-list-field-directive.js";
import UsersOpenListFieldDirective from "./fields/open-list-field/users-open-list-field-directive.js";
import PermissionFieldDirective from "./fields/permission-field/permission-field-directive.js";
import AwkwardCreationFields from "./model/awkward-creation-fields-constant.js";
import QuotaDisplayDirective from "./quota-display/quota-display-directive.js";
import HighlightDirective from "./tuleap-highlight/highlight-directive.js";
import ListPickerDirective from "./fields/list-picker-field/list-picker-field-directive.js";
import ListPickerMultipleDirective from "./fields/list-picker-multiple-field/list-picker-mulitple-field-directive.js";

import "./fields/computed-field/ComputedField";
import "./fields/float-field/FloatField";
import "./fields/int-field/IntField";
import "./fields/radio-buttons-field/RadioButtonsField";
import "./fields/string-field/StringField";
import "./fields/text-field/TextField";
import "./adapters/UI/fields/link-field-v2/LinkField";
import "./adapters/UI/fields/date-field/DateField";
import "./adapters/UI/fields/burndown-field/BurndownField";
import "./followups/FollowupEditor";
import "./adapters/UI/feedback/ModalFeedback";

export default angular
    .module("tuleap.artifact-modal", [
        angular_moment,
        "gettext",
        angular_tlp,
        angular_custom_elements_module,
        filter,
        ngSanitize,
    ])
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            for (const [language, strings] of Object.entries(french_translations)) {
                const short_language = language.split("_")[0];
                gettextCatalog.setStrings(short_language, strings);
                setCatalog(gettextCatalog);
            }
        },
    ])
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .controller("TuleapArtifactModalController", ArtifactModalController)
    .directive("tuleapArtifactModalFileField", FileFieldDirective)
    .directive("tuleapArtifactModalFileInput", FileInputDirective)
    .directive("tuleapArtifactModalLinkField", LinkFieldDirective)
    .directive("tuleapArtifactModalStaticOpenListField", StaticOpenListFieldDirective)
    .directive("tuleapArtifactModalUgroupsOpenListField", UgroupsOpenListFieldDirective)
    .directive("tuleapArtifactModalUsersOpenListField", UsersOpenListFieldDirective)
    .directive("tuleapArtifactModalPermissionField", PermissionFieldDirective)
    .directive("tuleapArtifactModalQuotaDisplay", QuotaDisplayDirective)
    .directive("tuleapHighlightDirective", HighlightDirective)
    .directive("tuleapArtifactModalListPickerField", ListPickerDirective)
    .directive("tuleapArtifactModalListPickerMultipleField", ListPickerMultipleDirective)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    }).name;
