<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div v-if="is_expired" class="tlp-alert-info">
        {{
            gettext_provider.interpolate(
                gettext_provider.$gettext(
                    "This banner has expired since %{ expiration_date } and, as such, is not displayed on the platform",
                ),
                { expiration_date: localized_expiration_date },
            )
        }}
    </div>
</template>

<script setup lang="ts">
import Vue, { computed } from "vue";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const gettext_provider = useGettext();

const props = defineProps<{
    readonly expiration_date: string;
    readonly message: string;
}>();

const is_expired = computed((): boolean => {
    if (props.message === "" || props.expiration_date === "") {
        return false;
    }

    return new Date() >= new Date(props.expiration_date);
});

const localized_expiration_date = computed((): string => {
    const locale = Vue.config.language ?? "en_US";
    return new Date(props.expiration_date).toLocaleString(locale.replace("_", "-"));
});
</script>
