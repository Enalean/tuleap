(<template>
    <tr>
        <td>{{ time_date }}</td>
        <td>{{ step }}</td>
        <td>{{ time }}</td>
    </tr>
</template>)

(<script>
import { formatMinutes } from "../../time-formatters.js";

export default {
    name: "WidgetModalRow",
    props: {
        timeData: Object
    },
    data() {
        return {
            step: this.timeData.step,
            time_date: this.timeData.date,
            time: formatMinutes(this.timeData.minutes)
        };
    }
};
</script>)
