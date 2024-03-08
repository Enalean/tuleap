<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div v-if="is_testplan_activated_value" class="past-release closed-release-header-badge">
        <i class="release-remaining-icon fa fa-check"></i>
        <span class="release-remaining-value" data-test="number-tests">
            {{ number_tests }}
        </span>
        <span class="release-remaining-text">{{ test_label }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import { is_testplan_activated } from "../../../helpers/test-management-helper";
import { useGettext } from "vue3-gettext";

const { $ngettext } = useGettext();

const props = defineProps<{ release_data: MilestoneData }>();

const number_tests = computed((): number => {
    if (!props.release_data.campaign) {
        return 0;
    }

    return (
        props.release_data.campaign.nb_of_failed +
        props.release_data.campaign.nb_of_blocked +
        props.release_data.campaign.nb_of_notrun +
        props.release_data.campaign.nb_of_passed
    );
});
const is_testplan_activated_value = computed((): boolean => {
    return is_testplan_activated(props.release_data);
});
const test_label = computed((): string => {
    return $ngettext("test", "tests", number_tests.value);
});
</script>
