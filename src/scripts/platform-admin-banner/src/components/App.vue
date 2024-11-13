<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
  -
  -->

<template>
    <div v-if="shouldDisplayErrorBanner" class="tlp-alert-danger" data-test="error-feedback">
        {{ error_banner_text }}
    </div>
    <div
        v-else-if="has_banner_been_modified"
        class="tlp-alert-success"
        data-test="success-feedback"
    >
        {{ $gettext("The banner has been successfully modified") }}
    </div>
    <expired-banner-info-message
        v-bind:message="message"
        v-bind:expiration_date="expiration_date"
    />
    <banner-presenter
        v-bind:message="message"
        v-bind:importance="importance"
        v-bind:expiration_date="expiration_date"
        v-bind:loading="banner_presenter_is_loading"
        v-on:save-banner="saveBanner"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import BannerPresenter from "./BannerPresenter.vue";
import { deleteBannerForPlatform, saveBannerForPlatform } from "../api/rest-querier";
import type { BannerState, Importance } from "../type";
import ExpiredBannerInfoMessage from "./ExpiredBannerInfoMessage.vue";
import type { LocationHelper } from "../helpers/LocationHelper";

const { $gettext } = useGettext();

const props = defineProps<{
    readonly message: string;
    readonly importance: Importance;
    readonly expiration_date: string;
    readonly location_helper: LocationHelper;
}>();

const error_message = ref<string | null>(null);
const has_banner_been_modified = ref(false);
const banner_presenter_is_loading = ref(false);

const shouldDisplayErrorBanner = computed((): boolean => error_message.value !== null);
const error_banner_text = computed((): string =>
    $gettext("An error occurred: %{ error_message }", { error_message: error_message.value ?? "" }),
);

onMounted(() => {
    if (props.location_helper.hasSuccessHash()) {
        props.location_helper.clearHash();
        has_banner_been_modified.value = true;
    }
});

function saveBanner(bannerState: BannerState): void {
    banner_presenter_is_loading.value = true;

    if (!bannerState.activated) {
        deleteBanner();
        return;
    }

    saveBannerMessage(bannerState.message, bannerState.importance, bannerState.expiration_date);
}

function saveBannerMessage(message: string, importance: Importance, expiration_date: string): void {
    saveBannerForPlatform(message, importance, expiration_date).match(
        props.location_helper.reloadWithSuccess,
        (fault) => {
            error_message.value = String(fault);
            banner_presenter_is_loading.value = false;
        },
    );
}

function deleteBanner(): void {
    deleteBannerForPlatform().match(props.location_helper.reloadWithSuccess, (fault) => {
        error_message.value = String(fault);
        banner_presenter_is_loading.value = false;
    });
}
</script>
