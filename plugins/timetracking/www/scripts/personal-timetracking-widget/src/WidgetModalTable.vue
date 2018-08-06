(<template>
    <table class="tlp-table">
        <thead>
        <tr>
            <th>{{ date_message }}</th>
            <th>{{ steps_label }}</th>
            <th>{{ time_label }}</th>
        </tr>
        </thead>
        <tbody>
        <widget-modal-row
            v-for="time in sorted_times"
            v-bind:time-data="time"
        />
        </tbody>
        <tfoot>
        <tr>
            <th></th>
            <th></th>
            <th class="tlp-table-last-row timetracking-total-sum">∑ {{ totalTime }}</th>
        </tr>
        </tfoot>
    </table>
</template>)

(<script>
import { gettext_provider } from "./gettext-provider.js";
import WidgetModalRow from "./WidgetModalRow.vue";

export default {
    name: "WidgetModalTable",
    components: { WidgetModalRow },
    props: {
        timeData: Array,
        totalTime: String
    },
    data() {
        return {
            times: this.timeData
        };
    },
    computed: {
        time_label: () => gettext_provider.gettext("Times"),
        date_message: () => gettext_provider.gettext("Date"),
        steps_label: () => gettext_provider.gettext("Steps"),
        sorted_times() {
            return this.times.sort((a, b) => {
                return new Date(b.date) - new Date(a.date);
            });
        }
    }
};
</script>)
