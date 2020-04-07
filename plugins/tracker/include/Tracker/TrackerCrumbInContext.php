<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tuleap\Event\Dispatchable;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;

class TrackerCrumbInContext implements Dispatchable
{
    public const NAME = 'trackerCrumbInContext';

    public const TRACKER_CRUMB_IDENTIFIER = 'tracker';

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var TrackerCrumbLinkInContext[]
     */
    private $go_to_links = [];
    /**
     * @var \PFUser
     */
    private $user;

    public function __construct(\Tracker $tracker, \PFUser $user)
    {
        $this->tracker = $tracker;
        $this->go_to_links[self::TRACKER_CRUMB_IDENTIFIER] = new TrackerCrumbLinkInContext(
            $this->tracker->getName(),
            sprintf(dgettext('tuleap-tracker', '%s tracker'), $this->tracker->getName()),
            TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId()
        );
        $this->user = $user;
    }

    public function getTracker(): \Tracker
    {
        return $this->tracker;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function addGoToLink(string $type, TrackerCrumbLinkInContext $link): void
    {
        $this->go_to_links[$type] = $link;
    }

    public function getCrumb(string $primary): BreadCrumb
    {
        if (! isset($this->go_to_links[$primary])) {
            throw new \LogicException('Primary link must have been added to collection before use');
        }

        $links = $this->go_to_links;

        $primary_link = $links[$primary];
        unset($links[$primary]);

        $crumb = new BreadCrumb(
            $primary_link->getPrimaryLink()
        );

        return $this->addSubItems($crumb, $links);
    }

    private function addSubItems(BreadCrumb $crumb, array $links): BreadCrumb
    {
        if (count($links) > 0) {
            $sub_items = new BreadCrumbSubItems();
            $link_collection = new BreadCrumbLinkCollection();
            foreach ($links as $link) {
                $link_collection[] = $link->getSecondaryLink();
            }
            $sub_items->addSection(new SubItemsSection(dgettext('tuleap-tracker', 'Shortcuts'), $link_collection));
            $crumb->setSubItems($sub_items);
        }
        return $crumb;
    }
}
