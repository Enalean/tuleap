<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/include/Codendi_HTMLPurifier.class.php');

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_Text extends Tracker_Artifact_ChangesetValue {
    /**
     * @const Changeset comment format is text.
     */
    const TEXT_CONTENT = 'text';

    /**
     * @const Changeset comment format is HTML
     */
    const HTML_CONTENT = 'html';

    /** @var string */
    protected $text;

    /** @var string */
    private $format;

    /**
     * @var array of purifier levels to be used when the content is displayed in text/plain context
     */
    public static $PURIFIER_LEVEL_IN_TEXT = array(
        'html' => CODENDI_PURIFIER_STRIP_HTML,
        'text' => CODENDI_PURIFIER_BASIC,
    );

    /**
     * @var array of purifier levels to be used when the content is displayed in text/html context
     */
    public static $PURIFIER_LEVEL_IN_HTML = array(
        'html' => CODENDI_PURIFIER_FULL,
        'text' => CODENDI_PURIFIER_BASIC,
    );
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_String $field       The field of the value
     * @param boolean                          $has_changed If the changeset value has chnged from the previous one
     * @param string                           $text        The string
     * @param string                           $format      The format
     */
    public function __construct($id, $field, $has_changed, $text, $format) {
        parent::__construct($id, $field, $has_changed);
        $this->text   = $text;
        $this->format = $format;
    }
    
    /**
     * Get the text value of this changeset value
     *
     * @return string the text
     */
    public function getText() {
        return $this->text;
    }

    public function getFormat() {
        return $this->format;
    }
    
    /**
     * Return a string that will be use in SOAP API
     * as the value of this ChangesetValue_Text 
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        return $this->encapsulateRawSoapValue($this->getText());
    }
    
    /**
     * Get the value (string)
     *
     * @return string The value of this artifact changeset value
     */
    public function getValue() {
        $hp = Codendi_HTMLPurifier::instance();

        if ($this->format == self::HTML_CONTENT) {
            return $hp->purify($this->getText(), self::$PURIFIER_LEVEL_IN_HTML[$this->format]);
        }

        return $hp->purify($this->getText(), self::$PURIFIER_LEVEL_IN_TEXT[$this->format]);
    }

    /**
     * Get the diff between this changeset value and the one passed in param
     *
     * @param Tracker_Artifact_ChangesetValue_Text $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html') {
        $previous = explode(PHP_EOL, $changeset_value->getText());
        $next     = explode(PHP_EOL, $this->getText());
        return $this->fetchDiff($previous, $next, $format);
    }
    
    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format='html') {
        $next = $this->getText();
        if ($next != '') {
            $previous = array('');
            $next     = explode(PHP_EOL, $this->getText());
            return $this->fetchDiff($previous, $next, $format);
        }
    }
    
    /**
    * Display the diff in changeset
    *
    * @return string The text to display
    */
    public function fetchDiff($previous, $next, $format) {
        $string = '';
        switch ($format) {
            case 'html':
                $callback = array(Codendi_HTMLPurifier::instance(), 'purify');
                $d = new Codendi_Diff(
                    array_map($callback, $previous, array_fill(0, count($previous), CODENDI_PURIFIER_CONVERT_HTML)),
                    array_map($callback, $next,     array_fill(0, count($next),     CODENDI_PURIFIER_CONVERT_HTML))
                );
                $f = new Codendi_HtmlUnifiedDiffFormatter();
                $diff = $f->format($d);
                if ($diff) {
                    $string .= '<div class="diff">'. $diff .'</div>';
                }
                break;
            case 'text':              
                $diff = new Codendi_Diff($previous, $next);
                $f    = new Codendi_UnifiedDiffFormatter();
                $string .= PHP_EOL.$f->format($diff);
                break;
            default:
                break;
        }
        return $string;
    }

}
?>
