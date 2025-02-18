<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\FollowUpPresenter;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Artifact\CodeBlockFeaturesOnArtifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueCommonmarkRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueTextRepresentation;

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_Text extends Tracker_Artifact_ChangesetValue // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @const Changeset comment format is text.
     */
    public const TEXT_CONTENT = 'text';

    /**
     * @const Changeset comment format is HTML
     */
    public const HTML_CONTENT = 'html';

    public const COMMONMARK_CONTENT = 'commonmark';

    private static $MAX_LENGTH_FOR_DIFF = 20000;

    /** @var string */
    protected $text;

    /** @var string */
    private $format;
    private Codendi_HTMLPurifier $purifier;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $text, $format)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->text   = $text;
        $this->format = $format;

        $this->purifier = Codendi_HTMLPurifier::instance();
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

    public function getFormat(): string
    {
        // Changing to === breaks some REST tests
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
        $post_processed_value = $this->getValue();

        if ($this->format === self::COMMONMARK_CONTENT) {
            $text_field_value = $this->interpretMarkdownContent($value);
            $commonmark_value = $value;
            return new ArtifactFieldValueCommonmarkRepresentation(
                $this->field->getId(),
                Tracker_FormElementFactory::instance()->getType($this->field),
                $this->field->getLabel(),
                $text_field_value,
                $commonmark_value,
                $post_processed_value,
            );
        }

        return new ArtifactFieldValueTextRepresentation(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value,
            $post_processed_value,
            $this->getFormat(),
        );
    }

    /**
     * Get the value (string)
     *
     * @return string The value of this artifact changeset value
     */
    public function getValue()
    {
        $interpreter = new TextValueInterpreter($this->purifier, self::getCommonMarkInterpreter($this->purifier));
        return $interpreter->interpretValueAccordingToFormat($this->getFormat(), $this->getText(), (int) $this->field->getTracker()->getProject()->getID());
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
    public function mailDiff(
        $changeset_value,
        $artifact_id,
        $changeset_id,
        $ignore_perms,
        $format = 'html',
        ?PFUser $user = null,
    ) {
        $previous = explode(PHP_EOL, $changeset_value->getText());
        $next     = explode(PHP_EOL, $this->getText());
        $string   = '';

        switch ($format) {
            case 'html':
                $formated_diff = $this->getFormattedDiff($previous, $next, CODENDI_PURIFIER_CONVERT_HTML);
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
        $url = \Tuleap\ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?aid=' . $artifact_id . '#followup_' . $changeset_id;

        return '<a href="' . $url . '">' . dgettext('tuleap-tracker', 'Go to diff') . '</a>';
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
            $previous = [''];
            $next     = explode(PHP_EOL, $this->getText());
            return $this->fetchDiff($previous, $next, $format);
        }
    }

    public function fetchDiff(array $previous, array $next, string $format): string
    {
        if ($previous === $next) {
            return '';
        }
        return $this->fetchDiffInFollowUp('');
    }

    protected function fetchDiffInFollowUp(string $formated_diff): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);

        $field = $this->getField();
        if (! $field instanceof Tracker_FormElement_Field_Text) {
            throw new LogicException('Field ' . $field->getId() . ' is not a text field');
        }

        $presenter = new FollowUpPresenter(
            $this->getChangeset()->getArtifact(),
            $field,
            $this
        );

        return $renderer->renderToString(
            'form-element/text/follow-up-content',
            $presenter
        );
    }

    public function getFormattedDiff(array $previous, array $next, int $purifier_level): string
    {
        $callback = [$this->purifier, 'purify'];
        $formater = new Codendi_HtmlUnifiedDiffFormatter();
        $diff     = new Codendi_Diff(
            array_map($callback, $previous, array_fill(0, count($previous), $purifier_level)),
            array_map($callback, $next, array_fill(0, count($next), $purifier_level))
        );

        return $formater->format($diff);
    }

    public function getContentAsText(): string
    {
        return self::getContentHasTextFromRawInfo($this->getText(), $this->format);
    }

    public static function getContentHasTextFromRawInfo(string $content, string $format): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        return match ($format) {
            self::HTML_CONTENT => $purifier->purify($content, CODENDI_PURIFIER_STRIP_HTML),
            self::COMMONMARK_CONTENT => self::getCommonMarkInterpreter($purifier)->getContentStrippedOfTags($content),
            default => $content
        };
    }

    public function getTextWithReferences(int $group_id): string
    {
        if ($this->isInHTMLFormat()) {
            return $this->purifier->purifyHTMLWithReferences($this->getText(), $group_id);
        }

        return $this->purifier->purifyTextWithReferences($this->getText(), $group_id);
    }

    private function isInHTMLFormat(): bool
    {
        return $this->format === self::HTML_CONTENT;
    }

    private function interpretMarkdownContent(string $text): string
    {
        $content_interpreter = self::getCommonMarkInterpreter($this->purifier);

        $interpreted_content_with_references = $content_interpreter->getInterpretedContentWithReferences(
            $text,
            (int) $this->changeset->getTracker()->getGroupId()
        );

        return $interpreted_content_with_references;
    }

    public static function getCommonMarkInterpreter(Codendi_HTMLPurifier $purifier): CommonMarkInterpreter
    {
        return CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(CodeBlockFeaturesOnArtifact::getInstance())
        );
    }

    public function setPurifier(Codendi_HTMLPurifier $purifier): void
    {
        $this->purifier = $purifier;
    }
}
