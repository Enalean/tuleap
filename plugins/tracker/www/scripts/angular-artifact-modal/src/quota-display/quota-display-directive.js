import './quota-display.tpl.html';
import QuotaDisplayController from './quota-display-controller.js';

export default function TuleapArtifactModalQuotaDisplay() {
    return {
        restrict: 'EA',
        replace : false,
        scope   : {
            disk_usage_empty: '=diskUsageEmpty'
        },
        controller      : QuotaDisplayController,
        controllerAs    : 'quota_display',
        bindToController: true,
        templateUrl     : 'quota-display.tpl.html'
    };
}
