angular
    .module('kanban')
    .controller('ReportsModalController', ReportsModalController);

ReportsModalController.$inject = [
    '$modalInstance'
];

function ReportsModalController(
    $modalInstance
) {
    var self = this;
    self.chartjs_options = {
        responsive         : true,
        maintainAspectRatio: false,
        scales             : {
            yAxes: [{
                stacked: true,
                ticks  : {
                    beginAtZero: true
                }
            }]
        }
    };

    self.chartjs_labels = [];
    self.chartjs_data   = [];
    self.chartjs_series = [];

    self.cancel = cancel;
    self.init = init;

    self.init();

    function init() {
    }

    function cancel() {
        $modalInstance.dismiss('cancel');
    }
}
