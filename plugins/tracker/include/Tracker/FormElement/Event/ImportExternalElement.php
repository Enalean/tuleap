<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Event;

use Project;
use SimpleXMLElement;
use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

class ImportExternalElement implements Dispatchable
{
    public const NAME = 'importExternalElement';

    /**
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * @var Tracker_FormElement_Field|null
     */
    private $form_element;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var TrackerXmlImportFeedbackCollector
     */
    private $feedback_collector;

    public function __construct(SimpleXMLElement $xml, Project $project, TrackerXmlImportFeedbackCollector $feedback_collector)
    {
        $this->xml     = $xml;
        $this->project = $project;
        $this->feedback_collector = $feedback_collector;
    }

    public function getFeedbackCollector(): TrackerXmlImportFeedbackCollector
    {
        return $this->feedback_collector;
    }

    public function getXml(): SimpleXMLElement
    {
        return $this->xml;
    }

    public function getFormElement(): ?Tracker_FormElement_Field
    {
        return $this->form_element;
    }

    public function setFormElement(Tracker_FormElement_Field $form_element): void
    {
        $this->form_element = $form_element;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
