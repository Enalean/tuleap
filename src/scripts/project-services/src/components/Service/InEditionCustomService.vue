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
        <service-id v-bind:value="service.id" />
        <service-label
            id="project-service-edit-modal-label"
            v-bind:value="service.label"
            v-on:input="onEditServiceLabel"
        />
        <icon-selector
            id="project-service-edit-modal-icon"
            v-bind:icon_name="service.icon_name"
            v-on:input="onEditIcon"
        />
        <service-is-used
            id="project-service-edit-modal-enabled"
            v-bind:value="service.is_used"
            v-bind:disabled-reason="service.is_disabled_reason"
        />
        <slot name="is_active">
            <hidden-service-is-active v-bind:is_active="service.is_active" />
        </slot>
        <service-rank id="project-service-edit-modal-rank" v-bind:value="service.rank" />
        <service-link id="project-service-edit-modal-link" v-bind:value="service.link" />
        <service-description
            id="project-service-edit-modal-description"
            v-bind:value="service.description"
        />

        <service-open-in-new-tab
            id="project-service-edit-modal-new-tab"
            v-bind:value="service.is_in_new_tab"
            v-on:input="onNewTabChange"
        />

        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': service.is_in_new_tab }"
            v-if="has_used_iframe"
        >
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    name="is_in_iframe"
                    value="1"
                    v-model="service.is_in_iframe"
                    v-bind:disabled="service.is_in_new_tab"
                    data-test="iframe-switch"
                />
                {{ $gettext("Display in iframe") }}
            </label>
        </div>

        <div
            class="tlp-alert-warning"
            v-if="is_iframe_deprecation_warning_shown || is_new_tab_warning_shown"
            ref="warnings"
        >
            <span v-if="is_new_tab_warning_shown" key="new_tab_warning" data-test="new-tab-warning">
                {{
                    $gettext(
                        "The service can't be displayed in an iframe because you want it to be open in a new tab.",
                    )
                }}
            </span>
            <span
                v-if="is_iframe_deprecation_warning_shown"
                key="iframe_deprecation_warning"
                data-test="iframe-deprecation-warning"
            >
                {{
                    $gettext(
                        "Opening in iframe is deprecated. If you switch it off, you won't be able to switch it back on again.",
                    )
                }}
            </span>
        </div>
    </div>
</template>
<script>
import ServiceId from "./ServiceId.vue";
import ServiceOpenInNewTab from "./ServiceOpenInNewTab.vue";
import IconSelector from "./IconSelector.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";

export default {
    name: "InEditionCustomService",
    components: {
        ServiceId,
        ServiceOpenInNewTab,
        HiddenServiceIsActive,
        ServiceRank,
        ServiceIsUsed,
        ServiceDescription,
        ServiceLink,
        ServiceLabel,
        IconSelector,
    },
    props: {
        service_prop: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            service: this.service_prop,
            has_used_iframe: this.service_prop.is_in_iframe,
            is_new_tab_warning_shown: false,
            is_iframe_deprecation_warning_shown: false,
        };
    },
    watch: {
        "service.is_in_iframe"(new_value) {
            this.is_iframe_deprecation_warning_shown = !new_value;
            if (new_value === false) {
                this.scrollWarningsIntoView();
            }
        },
    },
    methods: {
        onEditServiceLabel(new_label) {
            this.service.label = new_label;
        },
        onEditIcon(new_icon) {
            this.service.icon_name = new_icon;
        },
        onNewTabChange(new_tab) {
            this.service.is_in_new_tab = new_tab;
            if (this.service.is_in_iframe === true && new_tab === true) {
                this.service.is_in_iframe = false;
                this.is_new_tab_warning_shown = true;
                this.scrollWarningsIntoView();
            } else {
                this.is_new_tab_warning_shown = false;
            }
        },
        async scrollWarningsIntoView() {
            await this.$nextTick();
            if (
                typeof this.$refs.warnings !== "undefined" &&
                typeof this.$refs.warnings.scrollIntoView !== "undefined"
            ) {
                this.$refs.warnings.scrollIntoView(false);
            }
        },
    },
};
</script>
