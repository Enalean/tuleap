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
        <service-id v-bind:id="service.id" />
        <service-label
            id="project-service-edit-modal-label"
            v-bind:label="service.label"
            v-on:input="onEditServiceLabel"
        />
        <icon-selector
            id="project-service-edit-modal-icon"
            v-bind:icon_name="service.icon_name"
            v-on:input="onEditIcon"
        />
        <service-is-used
            id="project-service-edit-modal-enabled"
            v-bind:is_used="service.is_used"
            v-bind:is_disabled_reason="service.is_disabled_reason"
        />
        <slot name="is_active">
            <hidden-service-is-active v-bind:is_active="service.is_active" />
        </slot>
        <service-rank id="project-service-edit-modal-rank" v-bind:rank="service.rank" />
        <service-link
            id="project-service-edit-modal-link"
            v-bind:link="service.link"
            v-bind:disabled="false"
        />
        <service-description
            id="project-service-edit-modal-description"
            v-bind:description="service.description"
        />

        <service-open-in-new-tab
            id="project-service-edit-modal-new-tab"
            v-bind:is_in_new_tab="service.is_in_new_tab"
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
<script setup lang="ts">
import { ref, watch, nextTick, computed } from "vue";
import type { Service } from "../../type";
import ServiceId from "./ServiceId.vue";
import ServiceOpenInNewTab from "./ServiceOpenInNewTab.vue";
import IconSelector from "./IconSelector.vue";
import ServiceLabel from "./ServiceLabel.vue";
import ServiceLink from "./ServiceLink.vue";
import ServiceDescription from "./ServiceDescription.vue";
import ServiceIsUsed from "./ServiceIsUsed.vue";
import ServiceRank from "./ServiceRank.vue";
import HiddenServiceIsActive from "./HiddenServiceIsActive.vue";

const props = defineProps<{
    service_prop: Service;
}>();

const service = ref(props.service_prop);
const is_new_tab_warning_shown = ref(false);
const is_iframe_deprecation_warning_shown = ref(false);
const warnings = ref<HTMLElement | null>(null);

const has_used_iframe = computed(() => props.service_prop.is_in_iframe);

watch(has_used_iframe, (new_value: boolean) => {
    is_iframe_deprecation_warning_shown.value = !new_value;
    if (!new_value) {
        scrollWarningsIntoView();
    }
});

function onEditServiceLabel(new_label: string): void {
    service.value.label = new_label;
}

function onEditIcon(new_icon: string): void {
    service.value.icon_name = new_icon;
}

function onNewTabChange(is_in_new_tab: boolean): void {
    service.value.is_in_new_tab = is_in_new_tab;
    if (service.value.is_in_iframe === true && is_in_new_tab) {
        service.value.is_in_iframe = false;
        is_new_tab_warning_shown.value = true;
        scrollWarningsIntoView();
    } else {
        is_new_tab_warning_shown.value = false;
    }
}

async function scrollWarningsIntoView(): Promise<void> {
    await nextTick();
    if (
        typeof warnings.value !== "undefined" &&
        typeof warnings.value?.scrollIntoView !== "undefined"
    ) {
        warnings.value.scrollIntoView(false);
    }
}
</script>
