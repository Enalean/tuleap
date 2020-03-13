<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_Text extends Tracker_Artifact_ChangesetValue
{
    /**
     * @const Changeset comment format is text.
     */
    public const TEXT_CONTENT = 'text';

    /**
     * @const Changeset comment format is HTML
     */
    public const HTML_CONTENT = 'html';

    private static $MAX_LENGTH_FOR_DIFF = 20000;

    /** @var string */
    protected $text;

    /** @var string */
    private $format;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $text, $format)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->text   = $text;
        $this->format = $format;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitText($this);
    }

    /**
     * Get the text value of this changeset value
     *
     * @return string the text
     */
    public function getText()
    {
        return (string) $this->text;
    }

    public function getFormat()
    {
        if ($this->format == null) {
            return self::TEXT_CONTENT;
        }
        return $this->format;
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    public function getFullRESTValue(PFUser $user)
    {
        return $this->getFullRESTRepresentation($this->getText());
    }

    protected function getFullRESTRepresentation($value)
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueTextRepresentation();
        $artifact_field_value_full_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value,
            $this->getFormat()
        );

        return $artifact_field_value_full_representation;
    }

    /**
     * Get the value (string)
     *
     * @return string The value of this artifact changeset value
     */
    public function getValue()
    {
        $hp = Codendi_HTMLPurifier::instance();

        if ($this->isInHTMLFormat()) {
            return $hp->purifyHTMLWithReferences($this->getText(), $this->field->getTracker()->getProject()->getID());
        }
        return $hp->purifyTextWithReferences($this->getText(), $this->field->getTracker()->getProject()->getID());
    }

    /**
     * Get the diff between this changeset value and the one passed in param
     *
     * @return string|false The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous_text = $changeset_value->getText();
        $next_text     = $this->getText();
        if (strlen($previous_text) > self::$MAX_LENGTH_FOR_DIFF || strlen($next_text) > self::$MAX_LENGTH_FOR_DIFF) {
            return dgettext('tuleap-tracker', 'changed. (But text is too long, we are unable to compute the differences in a reasonable amount of time.)');
        }

        $previous = explode(PHP_EOL, $previous_text);
        $next     = explode(PHP_EOL, $next_text);
        return $this->fetchDiff($previous, $next, $format);
    }

    /**
     * @return false|string
     */
    public function modalDiff($changeset_value, $format = 'modal', ?PFUser $user = null)
    {
        return $this->diff($changeset_value, 'modal', $user);
    }

    /**
     * @return false|string
     */
    public function mailDiff(
        $changeset_value,
        $artifact_id,
        $changeset_id,
        $ignore_perms,
        $format = 'html',
        ?PFUser $user = null
    ) {
        $previous = explode(PHP_EOL, $changeset_value->getText());
        $next     = explode(PHP_EOL, $this->getText());
        $string   = '';

        switch ($format) {
            case 'html':
                $formated_diff = $this->getFormatedDiff($previous, $next);
                if ($formated_diff) {
                    $string = $this->fetchHtmlMailDiff($formated_diff, $artifact_id, $changeset_id);
                }
                break;
            case 'text':
                $diff      = new Codendi_Diff($previous, $next);
                $formatter = new Codendi_UnifiedDiffFormatter();
                $string    = PHP_EOL . $formatter->format($diff);
                break;
            default:
                break;
        }

        return $string;
    }

    /**
     * @return string text to be displayed in mail notifications when the text has been changed
     */
    protected function fetchHtmlMailDiff($formated_diff, $artifact_id, $changeset_id)
    {
        $url      = HTTPRequest::instance()->getServerUrl() . TRACKER_BASE_URL . '/?aid=' . $artifact_id . '#followup_' . $changeset_id;

        return '<a href="' . $url . '">' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'goto_diff') . '</a>';
    }

    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff($format = 'html')
    {
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
    public function fetchDiff($previous, $next, $format)
    {
        $string = '';
        switch ($format) {
            case 'text':
                $diff = new Codendi_Diff($previous, $next);
                $f    = new Codendi_UnifiedDiffFormatter();
                $string .= PHP_EOL . $f->format($diff);
                break;
            case 'html':
                $formated_diff = $this->getFormatedDiff($previous, $next);
                if ($formated_diff) {
                    $string = $this->fetchDiffInFollowUp($formated_diff);
                }
                break;
            case 'modal':
                $formated_diff = $this->getFormatedDiff($previous, $next);
                if ($formated_diff) {
                    $string = '<div class="diff">' . $formated_diff . '</div>';
                }
                break;
            default:
                break;
        }
        return $string;
    }

    /**
     * @return string text to be displayed in web ui when the text has been changed
     */
    protected function fetchDiffInFollowUp($formated_diff)
    {
        $html  = '';
        $html .= '<button class="btn btn-mini toggle-diff">' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'toggle_diff') . '</button>';
        $html .= '<div class="diff" style="display: none">' . $formated_diff . '</div>';

        return $html;
    }

    private function getFormatedDiff($previous, $next)
    {
        $callback = array(Codendi_HTMLPurifier::instance(), 'purify');
        $formater = new Codendi_HtmlUnifiedDiffFormatter();
        $diff     = new Codendi_Diff(
            array_map($callback, $previous, array_fill(0, count($previous), CODENDI_PURIFIER_CONVERT_HTML)),
            array_map($callback, $next, array_fill(0, count($next), CODENDI_PURIFIER_CONVERT_HTML))
        );

        return $formater->format($diff);
    }

    public function getContentAsText()
    {
        $hp = Codendi_HTMLPurifier::instance();
        if ($this->isInHTMLFormat()) {
            return $hp->purify($this->getText(), CODENDI_PURIFIER_STRIP_HTML);
        }

        return $this->getText();
    }

    private function isInHTMLFormat()
    {
        return $this->getFormat() == self::HTML_CONTENT;
    }
}
