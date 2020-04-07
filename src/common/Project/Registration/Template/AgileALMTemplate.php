<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\XML\ProjectXMLMerger;

class AgileALMTemplate implements TuleapTemplate
{
    public const NAME = 'agile_alm';

    private const PROJECT_XML = __DIR__ . '/../../../../../tools/utils/setup_templates/agile_alm/project.xml';
    private const AGILE_ALM_XML = __DIR__ . '/../../../../../tools/utils/setup_templates/agile_alm/agile_alm_template.xml';
    private const TEST_MANAGEMENT_XML = __DIR__ . '/../../../../../tools/utils/setup_templates/agile_alm/testmanagement.xml';

    /**
     * @var string
     */
    private $title;
    /**s
     * @var string
     */
    private $description;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var ProjectXMLMerger
     */
    private $project_xml_merger;
    /**
     * @var ConsistencyChecker
     */
    private $consistency_checker;
    /**
     * @var bool
     */
    private $available;
    /**
     * @var string
     */
    private $xml_path;

    public function __construct(GlyphFinder $glyph_finder, ProjectXMLMerger $project_xml_merger, ConsistencyChecker $consistency_checker)
    {
        $this->title               = _('Agile ALM');
        $this->description         = _('The most complete project workspace to plan, build and ship quality products. Include Tuleap Test Management, Time Tracking and Cross-Tracker Search. Reach end-to-end traceability.');
        $this->glyph_finder       = $glyph_finder;
        $this->project_xml_merger = $project_xml_merger;
        $this->consistency_checker = $consistency_checker;
    }

    /**
     * Actual XML file is generated "on the fly" in order to guaranty the consistency between the AgileDashboard
     * "Start Scrum" template and the "Project Creation" Scrum template
     */
    public function getXMLPath(): string
    {
        if ($this->xml_path === null) {
            $base_dir = \ForgeConfig::getCacheDir() . '/agile_alm_template';
            if (! is_dir($base_dir) && ! mkdir($base_dir, 0755) && ! is_dir($base_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $base_dir));
            }
            $this->xml_path = \ForgeConfig::getCacheDir() . '/agile_alm_template/project.xml';
            $this->project_xml_merger->merge(
                self::PROJECT_XML,
                self::AGILE_ALM_XML,
                $this->xml_path,
            );

            $testmanagment_file = \ForgeConfig::getCacheDir() . '/agile_alm_template/testmanagement.xml';
            if (! copy(self::TEST_MANAGEMENT_XML, $testmanagment_file)) {
                throw new \RuntimeException("Can not copy TTM file for tuleap template import");
            }
        }
        return $this->xml_path;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGlyph(): Glyph
    {
        return $this->glyph_finder->get(self::NAME);
    }

    public function getId(): string
    {
        return self::NAME;
    }

    public function isAvailable(): bool
    {
        if ($this->available === null) {
            $this->available = $this->consistency_checker->areAllServicesAvailable($this->getXMLPath());
        }
        return $this->available;
    }

    public function isBuiltIn(): bool
    {
        return true;
    }
}
