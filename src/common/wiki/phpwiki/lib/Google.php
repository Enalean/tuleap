<?php // -*- php -*-
/**
 * Google API
 *
 * @author: Chris Petersen, Reini Urban
 */
/*
 Copyright (c) 2002 Intercept Vector
 Copyright (c) 2004 Reini Urban

 This library is free software; you can redistribute it and/or
 modify it under the terms of the GNU Lesser General Public
 License as published by the Free Software Foundation; either
 version 2.1 of the License, or (at your option) any later version.

 This library is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 Lesser General Public License for more details.

 You should have received a copy of the GNU Lesser General Public
 License along with this library; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
 If you have any questions or comments, please email:

 Chris Petersen
 admin@interceptvector.com
 Intercept Vector
 http://www.interceptvector.com
*/

/*
 * @seealso: http://scripts.incutio.com/google/
 */

/*
 * Objectified, simplified, documented and added the two other queries 
 * by Reini Urban
 */

/**
 * GoogleSearchResults, list of GoogleSearch Result Elements
 *
 * Each time you issue a search request to the Google service, a
 * response is returned back to you. This section describes the
 * meanings of the values returned to you.
 *
 * <documentFiltering> - A Boolean value indicating whether filtering
 * was performed on the search results. This will be "true" only if
 * (a) you requested filtering and (b) filtering actually occurred.
 *
 * <searchComments> - A text string intended for display to an end
 * user. One of the most common messages found here is a note that
 * "stop words" were removed from the search automatically. (This
 * happens for very common words such as "and" and "as.")
 *
 * <estimatedTotalResultsCount> - The estimated total number of
 * results that exist for the query.  Note: The estimated number may
 * be either higher or lower than the actual number of results that
 * exist.
 *
 * <estimateIsExact> - A Boolean value indicating that the estimate is
 * actually the exact value.
 *
 * <resultElements> - An array of <resultElement> items. This
 * corresponds to the actual list of search results.
 *
 * <searchQuery> - This is the value of <q> for the search request.
 *
 * <startIndex> - Indicates the index (1-based) of the first search
 * result in <resultElements>.
 *
 * <endIndex> - Indicates the index (1-based) of the last search
 * result in <resultElements>.
 *
 * <searchTips> - A text string intended for display to the end
 * user. It provides instructive suggestions on how to use Google.
 *
 * <directoryCategories> - An array of <directoryCategory> items. This
 * corresponds to the ODP directory matches for this search.
 *
 * <searchTime> - Text, floating-point number indicating the total
 * server time to return the search results, measured in seconds.
 */

class GoogleSearchResults {
    var $_fields = "documentFiltering,searchComments,estimatedTotalResultsCount,estimateIsExact,searchQuery,startIndex,endIndex,searchTips,directoryCategories,searchTime,resultElements";
    var $resultElements, $results;

    function GoogleSearchResults ($result) {
        $this->fields = explode(',',$this->_fields);
        foreach ($this->fields as $f) {
            $this->{$f} = $result[$f];
        }
        $i = 0; $this->results = array();
        //$this->resultElements = $result['resultElements'];
        foreach ($this->resultElements as $r) {
            $this->results[] = new GoogleSearchResult($r);
        }
        return $this;
    }
}

/**
 *   Google Search Result Element:
 *
 *   <summary> - If the search result has a listing in the ODP
 *   directory, the ODP summary appears here as a text string.
 *
 *   <URL> - The URL of the search result, returned as text, with an
 *   absolute URL path.
 *
 *   <snippet> - A snippet which shows the query in context on the URL
 *   where it appears. This is formatted HTML and usually includes <B>
 *   tags within it. Note that the query term does not always appear
 *   in the snippet. Note: Query terms will be in highlighted in bold
 *   in the results, and line breaks will be included for proper text
 *   wrapping.
 *
 *   <title> - The title of the search result, returned as HTML.
 *
 *   <cachedSize> - Text (Integer + "k"). Indicates that a cached
 *   version of the <URL> is available; size is indicated in
 *   kilobytes.
 *
 *   <relatedInformationPresent> - Boolean indicating that the
 *   "related:" query term is supported for this URL.
 *
 *   <hostName> - When filtering occurs, a maximum of two results from
 *   any given host is returned. When this occurs, the second
 *   resultElement that comes from that host contains the host name in
 *   this parameter.
 *
 *   <directoryCategory> - array with "fullViewableName" and 
 *   "specialEncoding" keys.
 *
 *   <directoryTitle> - If the URL for this resultElement is contained
 *   in the ODP directory, the title that appears in the directory
 *   appears here as a text string. Note that the directoryTitle may
 *   be different from the URL's <title>.
 */
class GoogleSearchResult {
    var $_fields = "summary,URL,snippet,title,cachedSize,relatedInformationPresent,hostName,directoryCategory,directoryTitle";
    function GoogleSearchResult ($result) {
        $this->fields = explode(',',$this->_fields);
        foreach ($this->fields as $f) {
            $this->{$f} = $result[$f];
        }
        return $this;
    }
}

class Google {

    function Google($maxResults=10,$license_key=false,$proxy=false) {
        if ($license_key)
            $this->license_key = $license_key;
        elseif (!defined('GOOGLE_LICENSE_KEY')) {
            trigger_error("\nYou must first obtain a license key at http://www.google.com/apis/"
                         ."\nto be able to use the Google API.".
                          "\nIt's free however.", E_USER_WARNING);
            return false;
        }
        else
            $this->license_key = GOOGLE_LICENSE_KEY;
        require_once("lib/nusoap/nusoap.php");

        $this->soapclient = new soapclient(SERVER_URL . NormalizeWebFileName("GoogleSearch.wsdl"), "wsdl");
        $this->proxy = $this->soapclient->getProxy();
        if ($maxResults > 10) $maxResults = 10;
        if ($maxResults < 1) $maxResults = 1;
        $this->maxResults = $maxResults;
        return $this;
    }

    /** 
     * doGoogleSearch
     *
     * See http://www.google.com/help/features.html for examples of
     * advanced features.  Anything that works at the Google web site
     * will work as a query string in this method.
     * 
     * You can use the start and maxResults parameters to page through
     * multiple pages of results. Note that 'maxResults' is currently
     * limited by Google to 10.  See the API reference for more
     * advanced examples and a full list of country codes and topics
     * for use in the restrict parameter, along with legal values for
     * the language, inputencoding, and outputencoding parameters.
     *
     * <license key> Provided by Google, this is required for you to access the
     * Google service. Google uses the key for authentication and
     * logging.
     * <q> (See the API docs for details on query syntax.)
     * <start> Zero-based index of the first desired result.
     * <maxResults> Number of results desired per query. The maximum
     * value per query is 10.  Note: If you do a query that doesn't
     * have many matches, the actual number of results you get may be
     * smaller than what you request.
     * <filter> Activates or deactivates automatic results filtering,
     * which hides very similar results and results that all come from
     * the same Web host. Filtering tends to improve the end user
     * experience on Google, but for your application you may prefer
     * to turn it off. (See the API docs for more
     * details.)
     * <restrict> Restricts the search to a subset of the Google Web
     * index, such as a country like "Ukraine" or a topic like
     * "Linux." (See the API docs for more details.)
     * <safeSearch> A Boolean value which enables filtering of adult
     * content in the search results. See SafeSearch for more details.
     * <lr> Language Restrict - Restricts the search to documents
     * within one or more languages.
     * <ie> Input Encoding - this parameter has been deprecated and is
     * ignored. All requests to the APIs should be made with UTF-8
     * encoding. (See the API docs for details.)
     * <oe> Output Encoding - this parameter has been deprecated and is
     * ignored. All requests to the APIs should be made with UTF-8
     * encoding.
     */
    function doGoogleSearch($query, $startIndex=1, $maxResults=10, $filter = "false",
                            $restrict='', $safeSearch='false', $lr='',
                            $inputencoding='UTF-8', $outputencoding='UTF-8') {
        if (!$this->license_key)
            return false;
        // doGoogleSearch() gets created automatically!! (some eval'ed code from the soap request)
        $result = $this->proxy->doGoogleSearch($this->license_key, // "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                                        $query,
                                        $startIndex,
                                        $maxResults,
                                        $filter,
                                        $restrict,
                                        $safeSearch,
                                        $lr,
                                        $inputencoding, // ignored by server, everything is UTF-8 now
                                        $outputencoding);
        return new GoogleSearchResults($result);
    }

    /**
     * Retrieve a page from the Google cache.
     *
     * Cache requests submit a URL to the Google Web APIs service and
     * receive in return the contents of the URL when Google's
     * crawlers last visited the page (if available).
     *
     * Please note that Google is not affiliated with the authors of
     * cached pages nor responsible for their content.
     *
     * The return type for cached pages is base64 encoded text.
     *
     *  @params string url - full URL to the page to retrieve
     *  @return string full text of the cached page
     */
    function doGetCachedPage($url) {
        if (!$this->license_key)
            return false;
        // This method gets created automatically!! (some eval'ed code from the soap request)
        $result = $this->proxy->doGetCachedPage($this->license_key,
                                                $url);
        if (!empty($result)) return base64_decode($result);
    }

    /**
     * Get spelling suggestions from Google
     *
     * @param  string phrase   word or phrase to spell-check 
     * @return string          text of any suggested replacement, or None
     */
    function doSpellingSuggestion($phrase) {
        if (!$this->license_key)
            return false;
        // This method gets created automatically!! (some eval'ed code from the soap request)
        return $this->proxy->doSpellingSuggestion($this->license_key,
                                                  $phrase);
    }
}

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>