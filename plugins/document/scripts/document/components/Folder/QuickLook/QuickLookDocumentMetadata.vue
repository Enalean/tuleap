<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <section class="tlp-pane-section document-quick-look-properties">
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-id" class="tlp-label" v-translate>Id</label>
                <p id="document-id">#{{ item.id }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-owner" class="tlp-label" v-translate>Owner</label>
                <p id="document-owner">
                    <user-badge v-bind:user="item.owner"/>
                </p>
            </div>
        </div>
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-creation-date" class="tlp-label" v-translate>Creation date</label>
                <p id="document-creation-date">{{ getFormattedDate(item.creation_date) }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-last-update-date" class="tlp-label" v-translate>Last updated date</label>
                <p id="document-last-update-date">{{ getFormattedDate(item.last_update_date) }}</p>
            </div>
        </div>
    </section>
</template>
<script>
import moment from "moment";
import phptomoment from "phptomoment";
import { mapState } from "vuex";
import UserBadge from "../../User/UserBadge.vue";

export default {
    props: {
        item: Object
    },
    components: { UserBadge },
    computed: {
        ...mapState(["date_time_format"])
    },
    methods: {
        getFormattedDate(date) {
            return moment(date).format(phptomoment(this.date_time_format));
        }
    }
};
</script>
