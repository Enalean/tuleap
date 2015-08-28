angular
    .module('highlight.filter', [])
    .filter('tuleapHighlight', TuleapHighlightFilter);

TuleapHighlightFilter.$inject = [];

/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */

/**
 * Wraps the
 * @param text {string} haystack to search through
 * @param search {string} needle to search for
 * @param [caseSensitive] {boolean} optional boolean to use case-sensitive searching
 */
function TuleapHighlightFilter() {
  'use strict';

  function isTextSearchable(text, search) {
    return text && (search || angular.isNumber(search));
  }

  return function (text, search, caseSensitive) {
    if (! isTextSearchable(text, search)) {
      return text;
    }

    var flags = 'g';
    if (! caseSensitive) {
      flags += 'i';
    }

    text   = text.toString();
    search = _.escape(search.toString().replace(' ', '|'));

    return text.replace(new RegExp(search, flags), '<span class="highlight">$&</span>');
  };
}
