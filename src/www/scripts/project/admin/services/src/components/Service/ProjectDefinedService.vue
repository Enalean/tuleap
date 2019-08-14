<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-modal-body">
        <service-id v-bind:value="service.id"/>

        <div class="project-admin-services-edit-modal-top-fields">
            <service-label v-model="service.label"/>
            <service-is-used v-bind:value="service.is_used"/>
        </div>
        <div class="project-admin-services-edit-modal-top-fields">
            <icon-selector v-model="service.icon_name" v-bind:allowed_icons="allowed_icons"/>
            <slot name="is_active">
                <hidden-service-is-active v-bind:value="service.is_active"/>
            </slot>
        </div>

        <service-rank v-bind:minimal_rank="minimal_rank" v-bind:value="service.rank"/>
        <service-link v-bind:value="service.link"/>
        <service-description v-bind:value="service.description"/>

        <div class="tlp-form-element">
            <label class="tlp-label" for="project-admin-services-edit-modal-new-tab" v-translate>
                Open in a new tab
            </label>
            <div class="tlp-switch">
                <input
                    type="checkbox"
                    class="tlp-switch-checkbox"
                    id="project-admin-services-edit-modal-new-tab"
                    name="is_in_new_tab"
                    value="1"
                    v-bind:checked="service.is_in_new_tab"
                    v-on:change="onNewTabChange"
                    data-test="new-tab-switch"
                >
                <label
                    class="tlp-switch-button"
                    for="project-admin-services-edit-modal-new-tab"
                    aria-hidden
                ></label>
            </div>
        </div>

        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': service.is_in_new_tab }"
            v-if="has_used_iframe"
        >
            <label class="tlp-label" for="project-admin-services-edit-modal-iframe" v-translate>
                Display in iframe
            </label>
            <div class="tlp-switch">
                <input
                    class="tlp-switch-checkbox"
                    id="project-admin-services-edit-modal-iframe"
                    type="checkbox"
                    name="is_in_iframe"
                    value="1"
                    v-model="service.is_in_iframe"
                    v-bind:disabled="service.is_in_new_tab"
                    data-test="iframe-switch"
                >
                <label
                    class="tlp-switch-button"
                    for="project-admin-services-edit-modal-iframe"
                    aria-hidden
                ></label>
            </div>
        </div>

        <div
            class="tlp-alert-warning"
            v-if="is_iframe_deprecation_warning_shown || is_new_tab_warning_shown"
            ref="warnings"
        >
            <translate
                v-if="is_new_tab_warning_shown"
                key="new_tab_warning"
                data-test="new-tab-warning"
            >
                The service can't be displayed in an iframe because you want it to be open in a new
                tab.
            </translate>
            <translate
                v-if="is_iframe_deprecation_warning_shown"
                key="iframe_deprecation_warning"
                data-test="iframe-deprecation-warning"
            >
                Opening in iframe is deprecated. If you switch it off, you won't be able to switch
                it back on again.
            </translate>
        </div>
    </div>
</template>
<script>
import IconSelector from "./IconSelector.vue";
import ServiceId from "./ServiceId.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";
import { service_mixin } from "./service-mixin.js";

export default {
    name: "ProjectDefinedService",
    components: {
        HiddenServiceIsActive,
        ServiceRank,
        ServiceIsUsed,
        ServiceDescription,
        ServiceLink,
        ServiceLabel,
        IconSelector,
        ServiceId
    },
    mixins: [service_mixin],
    props: {
        allowed_icons: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            has_used_iframe: this.service.is_in_iframe,
            is_new_tab_warning_shown: false,
            is_iframe_deprecation_warning_shown: false
        };
    },
    watch: {
        "service.is_in_iframe"(new_value) {
            this.is_iframe_deprecation_warning_shown = !new_value;
            if (new_value === false) {
                this.scrollWarningsIntoView();
            }
        }
    },
    methods: {
        onNewTabChange($event) {
            const is_in_new_tab = $event.target.checked;
            this.service.is_in_new_tab = is_in_new_tab;
            if (this.service.is_in_iframe === true && is_in_new_tab === true) {
                this.service.is_in_iframe = false;
                this.is_new_tab_warning_shown = true;
                this.scrollWarningsIntoView();
            } else {
                this.is_new_tab_warning_shown = false;
            }
        },
        async scrollWarningsIntoView() {
            await this.$nextTick();
            this.$refs.warnings.scrollIntoView(false);
        }
    }
};
</script>
