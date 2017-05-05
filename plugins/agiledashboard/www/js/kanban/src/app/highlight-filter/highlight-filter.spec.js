/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */
import kanban_module from '../app.js';
import angular from 'angular';
import 'angular-mocks';

describe('tuleapHighlight', function() {
    var highlightFilter,
        testPhrase = 'Prefix Highlight Suffix';
    beforeEach(function() {
        angular.mock.module(kanban_module);

        angular.mock.inject(function($filter) {
            highlightFilter = $filter('tuleapHighlight');
        });
    });

    describe('case insensitive', function() {
        it('should highlight a matching phrase', function() {
            expect(highlightFilter(testPhrase, 'highlight')).toEqual('Prefix <span class="highlight">Highlight</span> Suffix');
        });
        it('should highlight nothing if no match found', function() {
            expect(highlightFilter(testPhrase, 'no match')).toEqual(testPhrase);
        });
        it('should highlight nothing for the undefined filter', function() {
            expect(highlightFilter(testPhrase, undefined)).toEqual(testPhrase);
        });
        it('should work correctly if text is null', function() {
            expect(highlightFilter(null, 'highlight')).toEqual(null);
        });
        it('should work correctly for number filters', function() {
            expect(highlightFilter('3210123', 0)).toEqual('321<span class="highlight">0</span>123');
        });
        it('should work correctly for number text', function() {
            expect(highlightFilter(3210123, '0')).toEqual('321<span class="highlight">0</span>123');
        });
    });

    describe('case sensitive', function() {
        it('should highlight a matching phrase', function() {
            expect(highlightFilter(testPhrase, 'Highlight', true)).toEqual('Prefix <span class="highlight">Highlight</span> Suffix');
        });
        it('should highlight nothing if no match found', function() {
            expect(highlightFilter(testPhrase, 'no match', true)).toEqual(testPhrase);
        });
        it('should highlight nothing for the undefined filter', function() {
            expect(highlightFilter(testPhrase, undefined, true)).toEqual(testPhrase);
        });
        it('should work correctly if text is null', function() {
            expect(highlightFilter(null, 'Highlight')).toEqual(null);
        });
        it('should work correctly for number filters', function() {
            expect(highlightFilter('3210123', 0, true)).toEqual('321<span class="highlight">0</span>123');
        });
        it('should work correctly for number text', function() {
            expect(highlightFilter(3210123, '0', true)).toEqual('321<span class="highlight">0</span>123');
        });
        it('should not highlight a phrase with different letter-casing', function() {
            expect(highlightFilter(testPhrase, 'highlight', true)).toEqual(testPhrase);
        });
    });

    it('should highlight nothing if empty filter string passed - issue #114', function() {
        expect(highlightFilter(testPhrase, '')).toEqual(testPhrase);
    });

    it('should highlight more that one element', function() {
        expect(highlightFilter(testPhrase, 'gh')).toEqual('Prefix Hi<span class="highlight">gh</span>li<span class="highlight">gh</span>t Suffix');
    });

    it('highlights each matching search terms', function() {
        expect(highlightFilter(testPhrase, 'suffix highlight')).toEqual('Prefix <span class="highlight">Highlight</span> <span class="highlight">Suffix</span>');
    });
});
