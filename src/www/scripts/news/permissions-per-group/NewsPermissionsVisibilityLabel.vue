/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(<template>
    <td v-if="isVisibilityPublic"
        class="permission-per-group-public-news"
    >
        <i class="fa fa-fw fa-unlock"></i>
        {{ public_news_label }}
    </td>

    <td v-else
        class="permission-per-group-private-news"
    >
        <i class="fa fa-fw fa-lock"></i>
        {{ private_news_label }}
    </td>
</template>)
(<script>
import { gettext_provider } from "./gettext-provider.js";

export default {
    name: "VisibilityLabel",
    props: {
        isVisibilityPublic: Boolean
    },
    computed: {
        private_news_label: () => gettext_provider.gettext("Private"),
        public_news_label: () => gettext_provider.gettext("Public")
    }
};
</script>)
