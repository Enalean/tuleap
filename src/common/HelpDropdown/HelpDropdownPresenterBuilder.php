<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\HelpDropdown;

use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;

class HelpDropdownPresenterBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(EventDispatcherInterface $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function build(PFUser $current_user, string $tuleap_version): HelpDropdownPresenter
    {
        $documentation = "/doc/" . urlencode($current_user->getShortLocale()) . "/";

        $main_items = [
            new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Get help'
                ),
                "/help/",
                "fa-life-saver"
            ),
            new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Documentation'
                ),
                $documentation,
                "fa-book"
            )
        ];

        $release_note = $this->getReleaseNoteLink($current_user, $tuleap_version);

        $explorer_endpoint_event = $this->event_dispatcher->dispatch(new \Tuleap\REST\ExplorerEndpointAvailableEvent());

        return new HelpDropdownPresenter(
            $main_items,
            $explorer_endpoint_event->getEndpointURL(),
            $release_note
        );
    }

    private function getReleaseNoteLink(PFUser $current_user, string $tuleap_version): ?HelpLinkPresenter
    {
        if ($current_user->useLabFeatures()) {
            return new HelpLinkPresenter(
                dgettext(
                    'tuleap-core',
                    'Release Note'
                ),
                $this->getActualReleaseLink($tuleap_version),
                "fa-star"
            );
        }
        return null;
    }

    private function getActualReleaseLink(string $tuleap_version): string
    {
        $version_number = str_replace(".", "-", substr($tuleap_version, 0, 5));

        return 'https://www.tuleap.org/ressources/release-notes/tuleap-' . urlencode($version_number);
    }
}
