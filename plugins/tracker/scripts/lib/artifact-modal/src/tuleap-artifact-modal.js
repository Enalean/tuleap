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

import "ngVue";
import "ngVue/build/plugins.js";
import Vue from "vue";
import TextField from "./fields/text-field/TextField.vue";
import FollowupEditor from "./followups/FollowupEditor.vue";
import NgVueConfig from "./ng-vue-config.js";

import "../../../../../../src/scripts/tuleap/custom-elements/relative-date";
import { STRUCTURAL_FIELDS } from "../../../constants/fields-constants.js";
import { setCatalog } from "./gettext-catalog";

import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";
import ComputedFieldDirective from "./fields/computed-field/computed-field-directive.js";
import DateFieldDirective from "./fields/date-field/date-field-directive.js";
import FileFieldDirective from "./fields/file-field/file-field-directive.js";
import FileInputDirective from "./fields/file-field/file-input-directive.js";
import LinkFieldDirective from "./fields/link-field/link-field-directive.js";
import StaticOpenListFieldDirective from "./fields/open-list-field/static-open-list-field-directive.js";
import UgroupsOpenListFieldDirective from "./fields/open-list-field/ugroups-open-list-field-directive.js";
import UsersOpenListFieldDirective from "./fields/open-list-field/users-open-list-field-directive.js";
import PermissionFieldDirective from "./fields/permission-field/permission-field-directive.js";
import focusOnClickDirective from "./tuleap-focus/focus-on-click-directive.js";
import AwkwardCreationFields from "./model/awkward-creation-fields-constant.js";
import QuotaDisplayDirective from "./quota-display/quota-display-directive.js";
import HighlightDirective from "./tuleap-highlight/highlight-directive.js";
import ListPickerDirective from "./fields/list-picker-field/list-picker-field-directive.js";
import ListPickerMultipleDirective from "./fields/list-picker-multiple-field/list-picker-mulitple-field-directive.js";
import RadioButtonFieldComponent from "./fields/radio-button-field/radio-button-field-component.js";

export default angular
    .module("tuleap.artifact-modal", [
        angular_moment,
        "gettext",
        "ngVue",
        "ngVue.plugins",
        angular_tlp,
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
    .config(NgVueConfig)
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .controller("TuleapArtifactModalController", ArtifactModalController)
    .directive("tuleapArtifactModalComputedField", ComputedFieldDirective)
    .directive("tuleapArtifactModalDateField", DateFieldDirective)
    .directive("tuleapArtifactModalFileField", FileFieldDirective)
    .directive("tuleapArtifactModalFileInput", FileInputDirective)
    .directive("tuleapArtifactModalLinkField", LinkFieldDirective)
    .directive("tuleapArtifactModalStaticOpenListField", StaticOpenListFieldDirective)
    .directive("tuleapArtifactModalUgroupsOpenListField", UgroupsOpenListFieldDirective)
    .directive("tuleapArtifactModalUsersOpenListField", UsersOpenListFieldDirective)
    .directive("tuleapArtifactModalPermissionField", PermissionFieldDirective)
    .directive("tuleapFocusOnClick", focusOnClickDirective)
    .directive("tuleapArtifactModalQuotaDisplay", QuotaDisplayDirective)
    .directive("tuleapHighlightDirective", HighlightDirective)
    .directive("tuleapArtifactModalListPickerField", ListPickerDirective)
    .directive("tuleapArtifactModalListPickerMultipleField", ListPickerMultipleDirective)
    .component("tuleapArtifactModalRadioButtonField", RadioButtonFieldComponent)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    })
    .value(TextField.name, Vue.component(TextField.name, TextField))
    .value(FollowupEditor.name, Vue.component(FollowupEditor.name, FollowupEditor)).name;
