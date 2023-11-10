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
  -
  -->

<template>
    <div>
        <div v-if="shouldDisplayErrorBanner" class="tlp-alert-danger">
            {{ translatedErrorMessage }}
        </div>
        <div v-else-if="has_banner_been_modified" class="tlp-alert-success">
            {{ $gettext("The banner has been successfully modified") }}
        </div>
        <div>
            <banner-presenter
                v-bind:message="message"
                v-bind:loading="banner_presenter_is_loading"
                v-on:save-banner="saveBanner"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import BannerPresenter from "./BannerPresenter.vue";
import { deleteBannerForProject, saveBannerForProject } from "../api/rest-querier";
import type { BannerState } from "../type";

const gettext_provider = useGettext();

const LOCATION_HASH_SUCCESS = "#banner-change-success";

const props = defineProps<{
    message: string;
    project_id: number;
    location: Location;
}>();

const error_message: Ref<string | null> = ref(null);
const has_banner_been_modified = ref(false);
const banner_presenter_is_loading = ref(false);
const location = ref(props.location);

const shouldDisplayErrorBanner = computed(() => error_message.value !== null);

const translatedErrorMessage = computed(() =>
    gettext_provider.interpolate(
        gettext_provider.$gettext("An error occurred: %{ error_message }"),
        {
            error_message: String(error_message.value),
        },
    ),
);

const refreshOnSuccessChange = (): void => {
    location.value.hash = LOCATION_HASH_SUCCESS;
    location.value.reload();
};

const deleteBanner = (): void => {
    deleteBannerForProject(props.project_id)
        .then(() => {
            refreshOnSuccessChange();
        })
        .catch((error) => {
            error_message.value = error.message;
            banner_presenter_is_loading.value = false;
        });
};

const saveBannerMessage = (message: string): void => {
    saveBannerForProject(props.project_id, message)
        .then(() => {
            refreshOnSuccessChange();
        })
        .catch((error) => {
            error_message.value = error.message;
            banner_presenter_is_loading.value = false;
        });
};

const saveBanner = (bannerState: BannerState): void => {
    banner_presenter_is_loading.value = true;

    if (!bannerState.activated) {
        deleteBanner();
        return;
    }

    saveBannerMessage(bannerState.message);
};

onMounted((): void => {
    if (location.value.hash === LOCATION_HASH_SUCCESS) {
        location.value.hash = "";
        has_banner_been_modified.value = true;
    }
});
</script>
