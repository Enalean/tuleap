describe('ExecutionListFilter', function() {
    beforeEach(module('ui.router'));
    beforeEach(module('angularFilterPack'));
    beforeEach(module('execution'));

    var list = [
        {
            "id": 24605,
            "uri": "executions/24605",
            "results": "",
            "status": "Passed",
            "last_update_date": null,
            //…
            "test_def": {
                "id": 24600,
                "uri": "testdef/24600",
                "summary": "Tracker Rule date verifications for a workflow",
                "category": "AgileDashboard"
            }
        },
        {
            "id": 24606,
            "uri": "executions/24606",
            "results": "",
            "status": "Failed",
            "last_update_date": null,
            //…
            "test_def": {
                "id": 24601,
                "uri": "testdef/24601",
                "summary": "Html notification for tracker v5",
                "category": "SOAP"
            }
        }
    ];

    it('it has a CampaignListFilter filter', inject(function($filter) {
        expect($filter('ExecutionListFilter')).not.toBeNull();
    }));

    it('it filters on category', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, 'soap');
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24606 }));
    }));

    it('it filters on summary', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, 'workflow');
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24605 }));
    }));

    it('it filters on test def id', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, '24601');
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24606 }));
    }));
});
