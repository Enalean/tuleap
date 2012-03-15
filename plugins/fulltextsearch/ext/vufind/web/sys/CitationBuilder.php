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
 * Citation Builder Class
 *
 * This class builds APA and MLA citations.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class CitationBuilder
{
    private $details;
    
    /**
     * Constructor
     *
     * Load the base data needed to build the citations.  The $details parameter
     * should contain as many of the following keys as possible:
     *
     *  authors         => Array of authors in "Last, First, Title, Dates" format.
     *                     i.e. King, Martin Luther, Jr., 1929-1968.
     *  title           => The primary title of the work.
     *  subtitle        => Subtitle of the work.
     *  edition         => Array of edition statements (i.e. "1st ed.").
     *  pubPlace        => Place of publication.
     *  pubName         => Name of publisher.
     *  pubDate         => Year of publication.
     *
     * Unless noted as an array, each field should be a string.
     *
     * @param   array       $details        An array of details used to build
     *                                      the citations.  See above for a full
     *                                      list of keys to populate.
     * @access  public
     */
    public function __construct($details)
    {
        $this->details = $details;
    }
    
    /**
     * Get APA citation.
     *
     * This function assigns all the necessary variables and then returns a template
     * name to display an APA citation.
     *
     * @access  public
     * @return  string                      Path to a Smarty template to display
     *                                      the citation.
     */
    public function getAPA()
    {
        global $interface;
        $apa = array(
            'title' => $this->getAPATitle(),
            'authors' => $this->getAPAAuthors(),
            'publisher' => $this->getPublisher(),
            'year' => $this->getYear(),
            'edition' => $this->getEdition()
        );
        $interface->assign('apaDetails', $apa);
        return 'Citation/apa.tpl';
    }
    
    /**
     * Get MLA citation.
     *
     * This function assigns all the necessary variables and then returns a template
     * name to display an MLA citation.
     *
     * @access  public
     * @return  string                      Path to a Smarty template to display
     *                                      the citation.
     */
    public function getMLA()
    {
        global $interface;
        $mla = array(
            'title' => $this->getMLATitle(),
            'authors' => $this->getMLAAuthors(),
            'publisher' => $this->getPublisher(),
            'year' => $this->getYear(),
            'edition' => $this->getEdition()
        );
        $interface->assign('mlaDetails', $mla);
        return 'Citation/mla.tpl';
    }
    
    /**
     * Is the string a valid name suffix?
     *
     * @access  private
     * @param   string      $str            The string to check.
     * @return  bool                        True if it's a name suffix.
     */
    private function isNameSuffix($str)
    {
        $str = $this->stripPunctuation($str);
        
        // Is it a standard suffix?
        $suffixes = array('Jr', 'Sr');
        if (in_array($str, $suffixes)) {
            return true;
        }
        
        // Is it a roman numeral?  (This check could be smarter, but it's probably
        // good enough as it is).
        if (preg_match('/^[MDCLXVI]+$/', $str)) {
            return true;
        }
        
        // If we got this far, it's not a suffix.
        return false;
    }
    
    /**
     * Is the string a date range?
     *
     * @access  private
     * @param   string      $str            The string to check.
     * @return  bool                        True if it's a date range.
     */
    private function isDateRange($str)
    {
        $str = trim($str);
        return preg_match('/^([0-9]+)-([0-9]*)\.?$/', $str);
    }
    
    /**
     * Abbreviate a first name.
     *
     * @access  private
     * @param   string      $name           The name to abbreviate
     * @return  string                      The abbreviated name.
     */
    private function abbreviateName($name)
    {
        $parts = explode(', ', $name);
        $name = $parts[0];
        
        // Attach initials... but if we encountered a date range, the name
        // ended earlier than expected, and we should stop now.
        if (isset($parts[1]) && !$this->isDateRange($parts[1])) {
            $fnameParts = explode(' ' , $parts[1]);
            for ($i = 0; $i < count($fnameParts); $i++) {
                $fnameParts[$i] = substr($fnameParts[$i], 0, 1) . '.';
            }
            $name .= ', ' . implode(' ', $fnameParts);
            if ($this->isNameSuffix($parts[2])) {
                $name = trim($name) . ', ' . $parts[2];
            }
        }
        
        return trim($name);
    }
    
    /**
     * Strip the dates off the end of a name.
     *
     * @access  private
     * @param   string      $str            Name to reverse.
     * @return  string                      Reversed name.
     */
    private function cleanNameDates($str)
    {
        $arr = explode(', ', $str);
        $name = $arr[0];
        if (isset($arr[1]) && !$this->isDateRange($arr[1])) {
            $name .= ', ' . $arr[1];
            if ($this->isNameSuffix($arr[2])) {
                $name .= ', ' . $arr[2];
            }
        }
        return $name;
    }
    
    /**
     * Strip unwanted punctuation from the right side of a string.
     *
     * @access  private
     * @param   string      $text           Text to clean up.
     * @return  string                      Cleaned up text.
     */
    private function stripPunctuation($text)
    {
        $text = trim($text);
        if ((substr($text, -1) == '.') ||
            (substr($text, -1) == ',') ||
            (substr($text, -1) == ':') ||
            (substr($text, -1) == ';') ||
            (substr($text, -1) == '/')) {
            $text = substr($text, 0, -1);
        }
        return trim($text);
    }
    
    /**
     * Turn a "Last, First" name into a "First Last" name.
     *
     * @access  private
     * @param   string      $str            Name to reverse.
     * @return  string                      Reversed name.
     */
    private function reverseName($str)
    {
        $arr = explode(', ', $str);
        
        // If the second chunk is a date range, there is nothing to reverse!
        if (!isset($arr[1]) || $this->isDateRange($arr[1])) {
            return $arr[0];
        }
        
        $name = $arr[1] . ' ' . $arr[0];
        if ($this->isNameSuffix($arr[2])) {
            $name .= ', ' . $arr[2];
        }
        return $name;
    }
    
    /**
     * Capitalize all words in a title, except for a few common exceptions.
     *
     * @access  private
     * @param   string      $str            Title to capitalize.
     * @return  string                      Capitalized title.
     */
    private function capitalizeTitle($str)
    {
        $exceptions = array('a', 'an', 'the', 'against', 'between', 'in', 'of', 
            'to', 'and', 'but', 'for', 'nor', 'or', 'so', 'yet', 'to');

        $words = split(' ', $str);
        $newwords = array();
        $followsColon = false;
        foreach ($words as $word) {
            // Capitalize words unless they are in the exception list...  but even
            // exceptional words get capitalized if they follow a colon.
            if (!in_array($word, $exceptions) || $followsColon) {
                $word = ucfirst($word);
            }
            array_push($newwords, $word);
            
            $followsColon = substr($word, -1) == ':';
        }
    
        return ucfirst(join(' ', $newwords));
    }

    /**
     * Get the full title for an APA citation.
     *
     * @access  private
     * @return  string
     */
    private function getAPATitle()
    {
        // Create Title
        $title = $this->stripPunctuation($this->details['title']);
        if (isset($this->details['subtitle'])) {
            $title .= ': ' . $this->stripPunctuation($this->details['subtitle']);
        }
        
        // Add period to titles not ending in punctuation
        if (!((substr($title, -1) == '?') || (substr($title, -1) == '!'))) {
            $title .= '.';
        }
        
        return $title;
    }
    
    /**
     * Get an array of authors for an APA citation.
     *
     * @access  private
     * @return  array
     */
    private function getAPAAuthors()
    {
        $authorStr = '';
        if (isset($this->details['authors']) && is_array($this->details['authors'])) {
            $i = 0;
            foreach($this->details['authors'] as $author) {
                $author = $this->abbreviateName($author);
                if (($i+1 == count($this->details['authors'])) && ($i > 0)) { // Last
                    $authorStr .= ', & ' . $this->stripPunctuation($author) . '.';
                } elseif ($i > 0) {
                    $authorStr .= ', ' . $this->stripPunctuation($author) . '.';
                } else {
                    $authorStr .= $this->stripPunctuation($author) . '.';
                }
                $i++;
            }
        }
        return (empty($authorStr) ? false : $authorStr);
    }
    
    /**
     * Get edition statement for inclusion in a citation.  Shared by APA and
     * MLA functionality.
     *
     * @access  private
     * @return  string
     */
    private function getEdition()
    {
        // Find the first edition statement that isn't "1st ed."
        if (isset($this->details['edition']) && 
            is_array($this->details['edition'])) {
            foreach($this->details['edition'] as $edition) {
                if ($edition !== '1st ed.') {
                    return $edition;
                }
            }
        }
        
        // No edition statement found:
        return false;
    }

    /**
     * Get the full title for an MLA citation.
     *
     * @access  private
     * @return  string
     */
    private function getMLATitle()
    {
        // MLA titles are just like APA titles, only capitalized differently:
        return $this->capitalizeTitle($this->getAPATitle());
    }
    
    /**
     * Get an array of authors for an APA citation.
     *
     * @access  private
     * @return  array
     */
    private function getMLAAuthors()
    {
        $authorStr = '';
        if (isset($this->details['authors']) && is_array($this->details['authors'])) {
            $i = 0;
            if (count($this->details['authors']) > 4) {
                $author = $this->details['authors'][0];
                $authorStr = $this->cleanNameDates($author) . ', et al';
            } else {
                foreach($this->details['authors'] as $author) {
                    if (($i+1 == count($this->details['authors'])) && ($i > 0)) { 
                        // Last
                        $authorStr .= ', and ' . 
                            $this->reverseName($this->stripPunctuation($author));
                    } elseif ($i > 0) {
                        $authorStr .= ', ' . 
                            $this->reverseName($this->stripPunctuation($author));
                    } else { 
                        // First
                        $authorStr .= $this->cleanNameDates($author);
                    }
                    $i++;
                }
            }
        }
        return (empty($authorStr) ? false : $this->stripPunctuation($authorStr));
    }
    
    /**
     * Get publisher information (place: name) for inclusion in a citation.
     * Shared by APA and MLA functionality.
     *
     * @access  private
     * @return  string
     */
    private function getPublisher()
    {
        $parts = array();
        if (isset($this->details['pubPlace']) && !empty($this->details['pubPlace'])) {
            $parts[] = $this->stripPunctuation($this->details['pubPlace']);
        }
        if (isset($this->details['pubName']) && !empty($this->details['pubName'])) {
            $parts[] = $this->details['pubName'];
        }
        if (empty($parts)) {
            return false;
        }
        return $this->stripPunctuation(implode(': ', $parts));
    }
    
    /**
     * Get the year of publication for inclusion in a citation.
     * Shared by APA and MLA functionality.
     *
     * @access  private
     * @return  string
     */
    private function getYear()
    {
        if (isset($this->details['pubDate'])) {
            return preg_replace('/[^0-9]/', '', $this->details['pubDate']);
        }
        return false;
    }
}
?>