export default TuleapArtifactModalQuotaDisplayController;

TuleapArtifactModalQuotaDisplayController.$inject = [
    '$scope',
    'TuleapArtifactModalFileUploadRules'
];

function TuleapArtifactModalQuotaDisplayController(
    $scope,
    TuleapArtifactModalFileUploadRules
) {
    var self = this;
    self.file_upload_rules      = TuleapArtifactModalFileUploadRules;
    self.getDiskUsagePercentage = getDiskUsagePercentage;
    self.init                   = init;
    self.isDiskUsageEmpty       = isDiskUsageEmpty;

    init();

    function init() {
        $scope.$watch(self.isDiskUsageEmpty, function(new_value) {
            self.disk_usage_empty = new_value;
        });
    }

    function isDiskUsageEmpty() {
        return self.file_upload_rules.disk_usage === 0;
    }

    function getDiskUsagePercentage() {
        return (self.file_upload_rules.disk_usage / self.file_upload_rules.disk_quota) * 100;
    }
}
