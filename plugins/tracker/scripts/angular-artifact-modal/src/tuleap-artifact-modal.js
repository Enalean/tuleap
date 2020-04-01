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
import "angular-gettext";
import translations from "../po/fr.po";

import "ngVue";
import "ngVue/build/plugins.js";
import Vue from "vue";
import "./vue-initializer.js";

import fields from "./tuleap-artifact-modal-fields/fields.js";
import model from "./model/model.js";
import quota_display from "./quota-display/quota-display.js";
import tuleap_highlight from "./tuleap-highlight/highlight.js";
import angular_tlp from "angular-tlp";

import FieldDependenciesService from "./field-dependencies-service.js";
import ValidateService from "./validate-service.js";
import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";

import TextField from "./tuleap-artifact-modal-fields/text-field/TextField.vue";
import FollowupEditor from "./followups/FollowupEditor.vue";
import NgVueConfig from "./ng-vue-config.js";

export default angular
    .module("tuleap.artifact-modal", [
        angular_moment,
        "gettext",
        "ngVue",
        "ngVue.plugins",
        angular_tlp,
        fields,
        model,
        ngSanitize,
        quota_display,
        tuleap_highlight,
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
    .controller("TuleapArtifactModalController", ArtifactModalController)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    })
    .service("TuleapArtifactModalFieldDependenciesService", FieldDependenciesService)
    .service("TuleapArtifactModalValidateService", ValidateService)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .value(TextField.name, Vue.component(TextField.name, TextField))
    .value(FollowupEditor.name, Vue.component(FollowupEditor.name, FollowupEditor)).name;
