<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <span class="pull-request-creation-info">
        {{ $gettext("Created") }}
        <tlp-relative-date
            data-test="pull-request-creation-date"
            v-bind:date="pull_request.creation_date"
            v-bind:absolute-date="formatted_full_date"
            v-bind:placement="relative_date_placement"
            v-bind:preference="relative_date_preference"
            v-bind:locale="user_locale"
            >{{ formatted_full_date }}</tlp-relative-date
        >
        {{ $gettext("by") }}
        <div class="pull-request-creation-info">
            <div class="tlp-avatar-mini">
                <img
                    v-bind:src="pull_request.creator.avatar_url"
                    aria-hidden="true"
                    data-test="pull-request-creator-avatar-img"
                />
            </div>
            <a
                class="pull-request-creator-name"
                data-test="pull-request-creator-link"
                v-bind:href="pull_request.creator.user_url"
                >{{ pull_request.creator.display_name }}</a
            >
        </div>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import moment from "moment";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { relativeDatePreference, relativeDatePlacement } from "@tuleap/tlp-relative-date";
import { formatFromPhpToMoment } from "@tuleap/date-helper";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
} from "../../../injection-symbols";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request: PullRequest;
}>();

const date_time_format: string = strictInject(USER_DATE_TIME_FORMAT_KEY);
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);
const user_locale: string = strictInject(USER_LOCALE_KEY);

const formatted_full_date = computed((): string => {
    return moment(props.pull_request.creation_date).format(formatFromPhpToMoment(date_time_format));
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_date_display);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_date_display, "right");
});
</script>

<style scoped lang="scss">
.pull-request-creation-info {
    display: flex;
    align-items: center;
    color: var(--tlp-dimmed-color);
    font-size: 0.75rem;
    gap: 0 4px;
}

.pull-request-creator-name {
    color: var(--tlp-dimmed-color);
}
</style>
