angular
    .module('tuleap-artifact-modal-quota-display')
    .directive('tuleapArtifactModalQuotaDisplay', TuleapArtifactModalQuotaDisplay);

TuleapArtifactModalQuotaDisplay.$inject = [];

function TuleapArtifactModalQuotaDisplay() {
    return {
        restrict: 'EA',
        replace : false,
        scope   : {
            disk_usage_empty: '=diskUsageEmpty'
        },
        controller      : 'TuleapArtifactModalQuotaDisplayController as quota_display',
        bindToController: true,
        templateUrl     : 'quota-display/quota-display.tpl.html'
    };
}
