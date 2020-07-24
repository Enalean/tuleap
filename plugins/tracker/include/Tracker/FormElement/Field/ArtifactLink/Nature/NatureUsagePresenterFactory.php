<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

class NatureUsagePresenterFactory
{
    /**
     * @var NatureDao
     */
    private $dao;

    public function __construct(NatureDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param NaturePresenter[] $natures
     *
     * @return NatureUsagePresenter[]
     */
    public function getNaturesUsagePresenters(array $natures)
    {
        $natures_usage_by_shortname = [];
        foreach ($natures as $nature) {
            $natures_usage_by_shortname[$nature->shortname] =  new NatureUsagePresenter(
                $nature,
                false
            );
        }

        foreach ($this->dao->getUsedNatures() as $row) {
            if (isset($natures_usage_by_shortname[$row['shortname']])) {
                $natures_usage_by_shortname[$row['shortname']]->setIsUsed(true);
            }
        }

        return array_values($natures_usage_by_shortname);
    }
}
