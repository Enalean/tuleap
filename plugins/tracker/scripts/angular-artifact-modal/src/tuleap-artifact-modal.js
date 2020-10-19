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
import translations from "../po/fr.po";

import "ngVue";
import "ngVue/build/plugins.js";
import Vue from "vue";
import "./vue-initializer.js";

import angular_tlp from "angular-tlp";

import FieldDependenciesService from "./field-dependencies-service.js";
import ValidateService from "./validate-service.js";
import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";

import TextField from "./tuleap-artifact-modal-fields/text-field/TextField.vue";
import FollowupEditor from "./followups/FollowupEditor.vue";
import NgVueConfig from "./ng-vue-config.js";
import "../../../../../src/themes/tlp/src/js/custom-elements/relative-date";
import ComputedFieldDirective from "./tuleap-artifact-modal-fields/computed-field/computed-field-directive.js";
import DateFieldDirective from "./tuleap-artifact-modal-fields/date-field/date-field-directive.js";
import FileFieldDirective from "./tuleap-artifact-modal-fields/file-field/file-field-directive.js";
import FileInputDirective from "./tuleap-artifact-modal-fields/file-field/file-input-directive.js";
import LinkFieldDirective from "./tuleap-artifact-modal-fields/link-field/link-field-directive.js";
import StaticOpenListFieldDirective from "./tuleap-artifact-modal-fields/open-list-field/static-open-list-field-directive.js";
import UgroupsOpenListFieldDirective from "./tuleap-artifact-modal-fields/open-list-field/ugroups-open-list-field-directive.js";
import UsersOpenListFieldDirective from "./tuleap-artifact-modal-fields/open-list-field/users-open-list-field-directive.js";
import PermissionFieldDirective from "./tuleap-artifact-modal-fields/permission-field/permission-field-directive.js";
import focusOnClickDirective from "./tuleap-focus/focus-on-click-directive.js";
import AwkwardCreationFields from "./model/awkward-creation-fields-constant.js";
import { STRUCTURAL_FIELDS } from "../../constants/fields-constants.js";
import FieldValuesService from "./model/field-values-service.js";
import TrackerTransformerService from "./model/tracker-transformer-service.js";
import QuotaDisplayDirective from "./quota-display/quota-display-directive.js";
import HighlightDirective from "./tuleap-highlight/highlight-directive.js";
import ListPickerDirective from "./tuleap-artifact-modal-fields/list-picker-field/list-picker-field-directive.js";
import ListPickerMultipleDirective from "./tuleap-artifact-modal-fields/list-picker-multiple-field/list-picker-mulitple-field-directive.js";

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
            for (const [language, strings] of Object.entries(translations)) {
                const short_language = language.split("_")[0];
                gettextCatalog.setStrings(short_language, strings);
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
    .service("TuleapArtifactModalFieldDependenciesService", FieldDependenciesService)
    .service("TuleapArtifactModalValidateService", ValidateService)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .service("TuleapArtifactFieldValuesService", FieldValuesService)
    .service("TuleapArtifactModalTrackerTransformerService", TrackerTransformerService)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    })
    .value(TextField.name, Vue.component(TextField.name, TextField))
    .value(FollowupEditor.name, Vue.component(FollowupEditor.name, FollowupEditor)).name;
