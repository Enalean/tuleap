describe('ExecutionListFilter', function() {
    beforeEach(module('ui.router'));
    beforeEach(module('angularFilterPack'));
    beforeEach(module('execution'));

    var list = [
        {
            "id": 24605,
            "uri": "executions/24605",
            "results": "",
            "status": "passed",
            "last_update_date": null,
            "assigned_to": null,
            //...
            "definition": {
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
            "status": "failed",
            "last_update_date": null,
            "assigned_to": {
                "id": 101,
                "uri": "users/101",
                "email": "renelataupe@example.com",
                "real_name": "rtaupe",
                "username": "rtaupe",
                "ldap_id": "",
                "avatar_url": "https://paelut/users/rtaupe/avatar.png"
            },
            //...
            "definition": {
                "id": 24601,
                "uri": "testdef/24601",
                "summary": "Html notification for tracker v5",
                "category": "SOAP"
            }
        },
        {
            "id": 24607,
            "uri": "executions/24607",
            "results": "",
            "status": "passed",
            "last_update_date": null,
            "assigned_to": {
                "id": 102,
                "uri": "users/102",
                "email": "joelclodo@example.com",
                "real_name": "jclodo",
                "username": "jclodo",
                "ldap_id": "",
                "avatar_url": "https://paelut/users/jclodo/avatar.png"
            },
            //…
            "definition": {
                "id": 24602,
                "uri": "testdef/24602",
                "summary": "Git test",
                "category": "GIT"
            }
        }
    ];

    it('it has a CampaignListFilter filter', inject(function($filter) {
        expect($filter('ExecutionListFilter')).not.toBeNull();
    }));

    it('it filters on category', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, 'soap', {}, null);
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24606 }));
    }));

    it('it filters on summary', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, 'workflow', {}, null);
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24605 }));
    }));

    it('it filters on test def id', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, '24601', {}, null);
        expect(results.length).toEqual(1);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24606 }));
    }));

    it('it filters on execution status', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, '', {passed: true}, null);
        expect(results.length).toEqual(2);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24605 }));
    }));

    it('it filters on execution multiple status', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, '', {passed: true, failed: true}, null);
        expect(results.length).toEqual(3);
        expect(results[0]).toEqual(jasmine.objectContaining({ id: 24605 }));
        expect(results[1]).toEqual(jasmine.objectContaining({ id: 24606 }));
        expect(results[2]).toEqual(jasmine.objectContaining({ id: 24607 }));
    }));

    it('it filters on summary and execution status', inject(function($filter) {
        var results = $filter('ExecutionListFilter')(list, 'tracker', {passed: true}, null);
        expect(results.length).toEqual(1);
    }));
});
