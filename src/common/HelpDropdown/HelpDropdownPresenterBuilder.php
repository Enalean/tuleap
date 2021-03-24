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

use ForgeConfig;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Sanitizer\URISanitizer;

class HelpDropdownPresenterBuilder
{
    /**
     * Set to 0 to hide Tuleap review link
     *
     * @tlp-config-key
     */
    public const  DISPLAY_TULEAP_REVIEW_LINK = 'display_tuleap_review_link';
    private const REVIEW_LINK                = 'https://www.tuleap.org/write-a-review';

    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var ReleaseNoteManager
     */
    private $release_note_manager;
    /**
     * @var URISanitizer
     */
    private $uri_sanitizer;

    public function __construct(
        ReleaseNoteManager $release_note_manager,
        EventDispatcherInterface $event_dispatcher,
        URISanitizer $uri_sanitizer
    ) {
        $this->event_dispatcher     = $event_dispatcher;
        $this->uri_sanitizer        = $uri_sanitizer;
        $this->release_note_manager = $release_note_manager;
    }

    public function build(PFUser $current_user, string $tuleap_version): HelpDropdownPresenter
    {
        $documentation = "/doc/" . urlencode($current_user->getShortLocale()) . "/";

        $main_items = [
            HelpLinkPresenter::build(
                dgettext(
                    'tuleap-core',
                    'Get help'
                ),
                "/help/",
                "fa-life-saver",
                $this->uri_sanitizer
            ),
            HelpLinkPresenter::build(
                dgettext(
                    'tuleap-core',
                    'Documentation'
                ),
                $documentation,
                "fa-book",
                $this->uri_sanitizer
            )
        ];

        $release_note = $this->getReleaseNoteLink($tuleap_version);
        $review_link  = $this->getReviewLink();

        if ($current_user->isAnonymous()) {
            $has_release_note_been_seen = true;
        } else {
            $has_release_note_been_seen = (bool) $current_user->getPreference("has_release_note_been_seen");
        }

        $explorer_endpoint_event = $this->event_dispatcher->dispatch(new \Tuleap\REST\ExplorerEndpointAvailableEvent());

        return new HelpDropdownPresenter(
            $main_items,
            $explorer_endpoint_event->getEndpointURL(),
            $review_link,
            $release_note,
            $has_release_note_been_seen,
            $this->getSiteContentLinks(),
        );
    }

    private function getReleaseNoteLink(string $tuleap_version): HelpLinkPresenter
    {
        $release_note_link = $this->release_note_manager->getReleaseNoteLink($tuleap_version);

        return HelpLinkPresenter::build(
            dgettext(
                'tuleap-core',
                'Release Note'
            ),
            $release_note_link,
            "fa-star",
            $this->uri_sanitizer
        );
    }

    /**
     * @return HelpLinkPresenter[]
     */
    private function getSiteContentLinks(): array
    {
        /**
         * We don't know if the site admin is doing nasty stuff in the extra_tabs.php,
         * so we have to check that our variable is still an array.
         * Psalm does not like that so we have to declare the variable as mixed
         * @psalm-var mixed
         */
        $additional_tabs = [];
        $filename        = $GLOBALS['Language']->getContent('layout/extra_tabs', null, null, '.php');
        if (! $filename || ! is_file($filename)) {
            return [];
        }

        include $filename;

        if (! is_array($additional_tabs)) {
            return [];
        }

        $links = [];
        foreach ($additional_tabs as $link) {
            if (isset($link['link'], $link['title']) && is_string($link['link']) && is_string($link['title'])) {
                $links[] = HelpLinkPresenter::build($link['title'], $link['link'], '', $this->uri_sanitizer);
            }
        }

        return $links;
    }

    private function getReviewLink(): ?HelpLinkPresenter
    {
        if (! ForgeConfig::get(self::DISPLAY_TULEAP_REVIEW_LINK)) {
            return null;
        }

        return HelpLinkPresenter::build(
            dgettext(
                'tuleap-core',
                'You enjoy Tuleap? Make a review'
            ),
            self::REVIEW_LINK,
            "fa-heart",
            $this->uri_sanitizer
        );
    }
}
