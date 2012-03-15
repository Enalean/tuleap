<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * World Cat Utilities
 *
 * Class for accessing helpful WorldCat APIs.
 */
class WorldCatUtils
{
    /**
     * Retrieve results from the index using the XISBN service.
     *
     * @param   string      $isbn       ISBN of main record
     * @return  array                   ISBNs for related items (may be empty).
     */
    public function getXISBN($isbn)
    {
        global $configArray;

        // Build URL
        $url = 'http://xisbn.worldcat.org/webservices/xid/isbn/' . 
                urlencode(is_array($isbn) ? $isbn[0] : $isbn) .
               '?method=getEditions&format=csv';
        if (isset($configArray['WorldCat']['id'])) {
            $url .= '&ai=' . $configArray['WorldCat']['id'];
        }

        // Print Debug code
        if ($configArray['System']['debug']) {
            echo "<pre>XISBN: $url</pre>";
        }

        // Fetch results
        $isbns = array();
        if ($fp = @fopen($url, "r")) {
            while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
                // Filter out non-ISBN characters and validate the length of
                // whatever is left behind; this will prevent us from treating 
                // error messages like "invalidId" or "overlimit" as ISBNs.
                $isbn = preg_replace('/[^0-9xX]/', '', $data[0]);
                if (strlen($isbn) < 10) {
                    continue;
                }
                $isbns[] = $isbn;
            }
        }

        return $isbns;
    }

    /**
     * Retrieve results from the index using the XISSN service.
     *
     * @param   string      $issn       ISSN of main record
     * @return  array                   ISSNs for related items (may be empty).
     */
    public function getXISSN($issn)
    {
        global $configArray;
    
        // Build URL
        $url = 'http://xissn.worldcat.org/webservices/xid/issn/' . 
                urlencode(is_array($issn) ? $issn[0] : $issn) .
               //'?method=getEditions&format=csv';
               '?method=getEditions&format=xml';
        if (isset($configArray['WorldCat']['id'])) {
            $url .= '&ai=' . $configArray['WorldCat']['id'];
        }

        // Print Debug code
        if ($configArray['System']['debug']) {
            echo "<pre>XISSN: $url</pre>";
        }

        // Fetch results
        $issns = array();
        $data = @file_get_contents($url);
        if (!empty($data)) {
            $unxml = new XML_Unserializer();
            $unxml->unserialize($data);
            $data = $unxml->getUnserializedData($data);
            if (!empty($data) && isset($data['group']['issn'])) {
                if (is_array($data['group']['issn'])) {
                    foreach ($data['group']['issn'] as $issn) {
                        $issns[] = $issn;
                    }
                } else {
                    $issns[] = $data['group']['issn'];
                }
            }
        }
        
        return $issns;
    }
    
    /**
     * Support function for getIdentitiesQuery(); is the provided name component
     * worth considering as a first or last name?
     *
     * @access  private
     * @param   string      $current        Name chunk to examine.
     * @return  boolean                     Should we use this as a name?
     */
    public function isUsefulNameChunk($current)
    {
        // Some common prefixes and suffixes that we do not want to treat as first
        // or last names:
        static $badChunks = array('jr', 'sr', 'ii', 'iii', 'iv', 'v', 'vi', 'vii',
            'viii', 'ix', 'x', 'junior', 'senior', 'esq', 'mr', 'mrs', 'miss', 'dr');
        
        // Clean up the input string:
        $current = str_replace('.', '', strtolower($current));
        
        // We don't want to use empty, numeric or known bad strings!
        if (empty($current) || is_numeric($current) ||
            in_array($current, $badChunks)) {
            return false;
        }
        return true;
    }
    
    /**
     * Support function for getRelatedIdentities() -- parse a name into a query
     * for WorldCat Identities.
     *
     * @access  private
     * @param   string      $name           Name to parse.
     * @return  mixed                       Boolean false if useless string;
     *                                      Identities query otherwise.
     */
    private function getIdentitiesQuery($name)
    {
        // Clean up user query and try to find name components within it:
        $name = trim(str_replace(array('"', ',', '-'), ' ', $name));
        $parts = explode(' ', $name);
        $first = $last = '';
        foreach($parts as $current) {
            $current = trim($current);
            // Do we want to store this chunk?
            if ($this->isUsefulNameChunk($current)) {
                // Is the first name empty?  If so, save this there.
                if (empty($first)) {
                    $first = $current;
                // If this isn't the first name, we always want to save it as the
                // last name UNLESS it's an initial, in which case we'll only save
                // it if we don't already have something better!
                } else if (strlen($current) > 2 || empty($last)) {
                    $last = $current;
                }
            }
        }
        
        // Fail if we found no useful name components; otherwise, build up the query
        // based on whether we found a first name only or both first and last names:
        if (empty($first) && empty($last)) {
            return false;
        } else if (empty($last)) {
            return "local.Name=\"{$first}\"";
        } else {
            return "local.Name=\"{$last}\" and local.Name=\"{$first}\"";
        }
    }

    /**
     * Support method for getRelatedIdentities() -- extract subject headings from 
     * the current node of the Identities API response.
     *
     * @access  private
     * @param   array           $current            Current response node.
     * @return  array                               Extracted subject headings.
     */
    private function processIdentitiesSubjects($current)
    {
        // Normalize subjects array if it has only a single entry:
        $subjects = isset($current['fastHeadings']['fast']) ?
            $current['fastHeadings']['fast'] : array();
        if (isset($subjects['tag'])) {
            $subjects = array($subjects);
        }

        // Collect subjects for current name:
        $retVal = array();
        if (is_array($subjects)) {
            foreach($subjects as $currentSubject) {
                if ($currentSubject['tag'] == '650' && 
                    !empty($currentSubject['_content'])) {
                    // Double dash will cause problems with Solr searches, so
                    // represent subject heading subdivisions differently:
                    $retVal[] = str_replace('--', ': ', $currentSubject['_content']);
                }
            }
        }
        
        return $retVal;
    }

    /**
     * Given a name string, get related identities.  Inspired by Eric Lease
     * Morgan's Name Finder demo (http://zoia.library.nd.edu/sandbox/name-finder/).
     * Return value is an associative array where key = author name and value =
     * subjects used in that author's works.
     *
     * @access  public
     * @param   string      $name           Name to search for (any format).
     * @param   int         $maxRecords     The maximum number of identity records
     *                                      to consult via the API (more = slower).
     * @return  mixed                       False on error, otherwise array of
     *                                      related names.
     */
    public function getRelatedIdentities($name, $maxRecords = 10)
    {
        // Build the WorldCat Identities API query:
        $query = $this->getIdentitiesQuery($name);
        if (!$query) {
            return false;
        }
        
        // Get the API response:
        $url = "http://worldcat.org/identities/search/PersonalIdentities" .
            "?query=" . urlencode($query) .
            "&version=1.1" .
            "&operation=searchRetrieve" .
            "&recordSchema=info%3Asrw%2Fschema%2F1%2FIdentities" .
            "&maximumRecords=" . intval($maxRecords) .
            "&startRecord=1" .
            "&resultSetTTL=300" .
            "&recordPacking=xml" .
            "&recordXPath=" .
            "&sortKeys=holdingscount";
        $data = @file_get_contents($url);

        // Translate XML to array:
        $unxmlOptions = array(
            XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => true
            );
        $unxml = new XML_Unserializer($unxmlOptions);
        $unxml->unserialize($data);
        $data = $unxml->getUnserializedData($data);

        // Give up if expected data is missing:
        if (!isset($data['records']['record'])) {
            return false;
        }

        // Normalize single record special case for foreach compatibility:
        $baseData = $data['records']['record'];
        if (isset($baseData['recordData'])) {
            $baseData = array($baseData);
        }

        // Loop through data and collect names and related subjects:
        $processedData = array();
        foreach($baseData as $current) {
            // Build current name string:
            $current = isset($current['recordData']['Identity']['nameInfo']) ?
                $current['recordData']['Identity']['nameInfo'] : array();
            if (isset($current['type']) && $current['type'] == 'personal' && 
                !empty($current['rawName']['suba'])) {
                $currentName = $current['rawName']['suba'] .
                    (isset($current['rawName']['subd']) ? 
                        ', ' . $current['rawName']['subd'] : '');

            // Get subject list for current identity; if the current name is a 
            // duplicate of a previous name, merge the subjects together:
            $subjects = $this->processIdentitiesSubjects($current);
            $processedData[$currentName] = isset($processedData[$currentName]) ?
                array_unique(array_merge($processedData[$currentName], $subjects)) :
                $subjects;
            }
        }

        return $processedData;
    }
    
    /**
     * Given a subject term, get related (broader/narrower/alternate) terms.
     * Loosely adapted from Eric Lease Morgan's Term Finder demo (see
     * http://zoia.library.nd.edu/sandbox/term-finder/).  Note that this is
     * intended as a fairly fuzzy search -- $term need not be an exact subject
     * heading; this function will return best guess matches in the 'exact'
     * key, possible broader terms in the 'broader' key and possible narrower
     * terms in the 'narrower' key of the return array.
     *
     * @access  public
     * @param   string      $term           Term to get related terms for.
     * @param   string      $vocabulary     Vocabulary to search (default = LCSH;
     *                                      see OCLC docs for other options).
     * @param   int         $maxRecords     The maximum number of authority records
     *                                      to consult via the API (more = slower).
     * @return  mixed                       False on error, otherwise array of
     *                                      related terms, keyed by category.
     */
    public function getRelatedTerms($term, $vocabulary = 'lcsh', $maxRecords = 10)
    {
        // Strip quotes from incoming term:
        $term = str_replace('"', '', $term);

        // Build the request URL:
        $url = "http://tspilot.oclc.org/" . urlencode($vocabulary) . "/?" .
            // Search for the user-supplied term in both preferred and alternative fields!
            "query=oclcts.preferredTerm+%3D+%22" . urlencode($term) . 
                "%22+OR+oclcts.alternativeTerms+%3D+%22" . urlencode($term) . "%22" .
            "&version=1.1" .
            "&operation=searchRetrieve" .
            "&recordSchema=info%3Asrw%2Fschema%2F1%2Fmarcxml-v1.1" .
            "&maximumRecords=" . intval($maxRecords) .
            "&startRecord=1" .
            "&resultSetTTL=300" .
            "&recordPacking=xml" .
            "&recordXPath=" .
            "&sortKeys=";

        // Get the API response:
        $data = @file_get_contents($url);

        // Extract plain MARCXML from the WorldCat response:
        $style = new DOMDocument;
        $style->load('xsl/wcterms-marcxml.xsl');
        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $xml = new DOMDocument;
        $xml->loadXML($data);
        $marcxml = $xsl->transformToXML($xml);

        // Try to parse the MARCXML into a File_MARC object; if this fails,
        // we probably have bad MARCXML, which may indicate an API failure
        // or an empty record set.  Just give up if this happens!
        try {
            $marc = new File_MARCXML($marcxml, File_MARCXML::SOURCE_STRING);
        } catch (File_MARC_Exception $e) {
            return false;
        }

        // Initialize arrays:
        $exact = array();
        $broader = array();
        $narrower = array();
        
        while ($record = $marc->next()) {
            // Get exact terms:
            $actual = $record->getField('150');
            if ($actual) {
                $main = $actual->getSubfield('a');
                if ($main) {
                    // Some versions of File_MARCXML seem to have trouble returning
                    // strings properly (giving back XML objects instead); let's
                    // cast to string to be sure we get what we expect!
                    $main = (string)$main->getData();
                    
                    // Add subdivisions:
                    $subdivisions = $actual->getSubfields('x');
                    if ($subdivisions) {
                        foreach($subdivisions as $current) {
                            $main .= ', ' . (string)$current->getData();
                        }
                    }
                    
                    // Only save the actual term if it is not a subset of the
                    // requested term.
                    if (!stristr($term, $main)) {
                        $exact[] = $main;
                    }
                }
            }
            
            // Get broader/narrower terms:
            $related = $record->getFields('550');
            foreach($related as $current) {
                $type = $current->getSubfield('w');
                $value = $current->getSubfield('a');
                if ($type && $value) {
                    $type = (string)$type->getData();
                    $value = (string)$value->getData();
                    if ($type == 'g') {
                        // Don't save exact matches to the user-entered term:
                        if (strcasecmp($term, $value) != 0) {
                            $broader[] = $value;
                        }
                    } else if ($type == 'h') {
                        // Don't save exact matches to the user-entered term:
                        if (strcasecmp($term, $value) != 0) {
                            $narrower[] = $value;
                        }
                    }
                }
            }
        }
        
        // Send back everything we found, sorted and filtered for uniqueness:
        natcasesort($exact);
        natcasesort($broader);
        natcasesort($narrower);
        return array(
            'exact' => array_unique($exact),
            'broader' => array_unique($broader),
            'narrower' => array_unique($narrower)
            );
    }
}

?>