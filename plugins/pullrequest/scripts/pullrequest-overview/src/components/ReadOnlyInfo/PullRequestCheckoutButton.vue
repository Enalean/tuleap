<!--
  - Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
    <div class="tlp-dropdown">
        <button
            id="pull-request-checkout-dropdown"
            class="tlp-button-primary pull-request-checkout-dropdown-button"
            type="button"
            ref="dropdown_button"
            v-bind:disabled="pull_request_info === null"
        >
            <i class="tlp-button-icon fa-solid fa-circle-arrow-down"></i>
            {{ $gettext("Checkout") }}
            <i class="tlp-button-icon-right fa-solid fa-caret-down"></i>
        </button>

        <div class="tlp-dropdown-menu pull-request-checkout-dropdown" role="menu">
            <div class="pull-request-checkout-dropdown-title">
                <span class="pull-request-menu-label">
                    {{ $gettext("Checkout with") }}
                </span>
                <select
                    class="tlp-select tlp-select-adjusted tlp-select-small"
                    v-on:change="updateCheckoutOption"
                >
                    <option
                        v-if="
                            props.pull_request_info &&
                            props.pull_request_info.repository_dest.clone_ssh_url
                        "
                        v-bind:selected="checkout_option === SSH"
                        v-bind:value="SSH"
                    >
                        {{ $gettext("SSH") }}
                    </option>
                    <option
                        v-if="
                            props.pull_request_info &&
                            props.pull_request_info.repository_dest.clone_http_url
                        "
                        v-bind:selected="checkout_option === HTTP"
                        v-bind:value="HTTP"
                    >
                        {{ $gettext("HTTP") }}
                    </option>
                </select>
            </div>
            <pre class="pull-request-commands" data-test="pull-request-commands">{{ getCloneInfo }}
git checkout FETCH_HEAD
                <copy-to-clipboard
                    class="tlp-append tlp-button-secondary tlp-button-outline tlp-button-mini pull-request-copy-url-button tlp-tooltip tlp-tooltip-left"
                    v-bind:value="`${getCloneInfo} && git checkout FETCH_HEAD`"
                    v-bind:data-tlp-tooltip="tooltip_label"
                    v-on:copied-to-clipboard="copyUrl"
                    ref="copy_clipboard">
                    <i class="fa-solid fa-clipboard tlp-button-icon" aria-hidden="true"></i>
                    {{ $gettext("Copy") }}
                </copy-to-clipboard>
            </pre>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { computed, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { createDropdown } from "@tuleap/tlp-dropdown";
import "@tuleap/copy-to-clipboard";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();

const SSH = "ssh";
const HTTP = "http";

const tooltip_label = ref($gettext("Copy to clipboard"));
const dropdown_button = ref<HTMLButtonElement | null>(null);
const checkout_option = ref("");

watch(
    () => props.pull_request_info,
    (): void => {
        if (!props.pull_request_info) {
            return;
        }
        checkout_option.value = props.pull_request_info.repository_dest.clone_ssh_url ? SSH : HTTP;
        if (!dropdown_button.value) {
            return;
        }

        createDropdown(dropdown_button.value);
    },
);

function updateCheckoutOption(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }
    checkout_option.value = event.target.value;
}

const getCloneInfo = computed((): string => {
    if (!props.pull_request_info) {
        return "";
    }
    if (checkout_option.value === HTTP) {
        return `git fetch ${props.pull_request_info.repository_dest.clone_http_url} ${props.pull_request_info.head_reference}`;
    }

    return `git fetch ${props.pull_request_info.repository_dest.clone_ssh_url} ${props.pull_request_info.head_reference}`;
});

function copyUrl(): void {
    tooltip_label.value = $gettext("Command lines copied to clipboard");
}
</script>

<style lang="scss">
.tlp-dropdown-menu.tlp-dropdown-shown.pull-request-checkout-dropdown {
    display: flex;
    flex-direction: column;
    gap: var(--tlp-small-spacing);
    padding: var(--tlp-medium-spacing);
}

.pull-request-checkout-dropdown-title {
    display: flex;
}

.pull-request-menu-label {
    margin: var(--tlp-small-spacing) var(--tlp-small-spacing) 0 0;
}

.pull-request-commands {
    display: flex;
    align-items: baseline;
}
</style>
