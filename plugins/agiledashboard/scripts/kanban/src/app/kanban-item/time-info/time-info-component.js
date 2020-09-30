import moment from "moment";
import { isBacklog } from "../../kanban-column/kanban-column-identifier.js";

controller.$inject = ["$sce", "gettextCatalog"];

function controller($sce, gettextCatalog) {
    const self = this;

    Object.assign(self, {
        isBacklog,
        getTimeInfo,
    });

    function getTimeInfo(item) {
        let timeinfo = "";

        if (!item.in_column || !item.timeinfo) {
            return "";
        }

        timeinfo += getTimeInfoEntry(
            item.timeinfo.kanban,
            gettextCatalog.getString("In Kanban since:")
        );
        timeinfo += "\u000a\u000a";
        timeinfo += getTimeInfoEntry(
            item.timeinfo[item.in_column],
            gettextCatalog.getString("In column since:")
        );

        return $sce.trustAsHtml(timeinfo);
    }

    function getTimeInfoEntry(entry_date, label) {
        if (!entry_date) {
            return "";
        }

        return `${label} ${moment(entry_date).calendar()}`;
    }
}

export default {
    template: `
        <span ng-if="::(! $ctrl.isBacklog($ctrl.item.in_column))"
            class="kanban-item-content-clock"
            title="{{ ::$ctrl.getTimeInfo($ctrl.item) }}"
        >
            <i class="far fa-clock"></i>
        </span>`,
    controller,
    bindings: {
        item: "<",
    },
};
