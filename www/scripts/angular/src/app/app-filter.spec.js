import trafficlights_module from './app.js';
import angular from 'angular';
import 'angular-mocks';

describe('InPropertiesFilter', function() {
    var ngFilter;

    beforeEach(angular.mock.module(trafficlights_module));
    beforeEach(angular.mock.inject(function($filter) {
        ngFilter = $filter;
    }));

    var properties = ['label', 'status'],
        list = [
            {id: 1, label: 'Valid 7.11', status: 'First status'},
            {id: 2, label: 'Valid 7.11', status: 'Second status'},
            {id: 3, label: 'Valid 8', status: 'First status'},
            {id: 4, label: 'Valid 8 beta', status: 'First status'},
            {id: 5, label: 'Valid status', status: 'First status'},
            {id: 6, label: 'Valid 9', status: 'Plop'}
        ];

    it('it has a InPropertiesFilter filter', function() {
        expect(ngFilter('InPropertiesFilter')).not.toBeNull();
    });

    it('it filters on campaign label', function() {
        expect(ngFilter('InPropertiesFilter')(list, 'beta', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
    });

    it('it filters on campaign status', function() {
        expect(ngFilter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 3, label: 'Valid 8', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 5, label: 'Valid status', status: 'First status'}
        );
    });

    it('it filters on both status', function() {
        expect(ngFilter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 2, label: 'Valid 7.11', status: 'Second status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 3, label: 'Valid 8', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
        expect(ngFilter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 5, label: 'Valid status', status: 'First status'}
        );
    });
});
