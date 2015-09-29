<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require 'bootstrap.php';
require_once dirname(__FILE__).'/../include/MediawikiLanguageManager.php';
require_once dirname(__FILE__).'/../include/MediawikiWelcomePageManager.php';
require_once dirname(__FILE__).'/../include/ServiceMediawiki.class.php';

class MediawikiWelcomePageManagerTest extends TuleapTestCase {

    /** @var MediawikiLanguageManager */
    private $language_manager;

    /** @var MediawikiWelcomePageManager */
    private $welcome_page_manager;

    /** @var Project */
    private $project;

    /** @var HTTPRequest */
    private $request;

    /** @var ServiceMediawiki */
    private $service;

    public function setUp() {
        parent::setUp();
        $this->language_manager     = mock('MediawikiLanguageManager');
        $this->welcome_page_manager = partial_mock('MediawikiWelcomePageManager', array('exterminate'),array($this->language_manager));
        $this->project              = mock('Project');
        $this->request              = mock('HTTPRequest');
        $this->service              = mock('ServiceMediawiki');

        stub($this->project)->getService(MediaWikiPlugin::SERVICE_SHORTNAME)->returns($this->service);
        stub($this->language_manager)->getAvailableLanguages()->returns(array('en_US', 'fr_FR'));
    }

    public function itDisplaysTheAlternativeWelcomePageIfNoLanguageIsDefinedForProject() {
        stub($this->language_manager)->getUsedLanguageForProject($this->project)->returns(null);

        expect($this->service)->renderInPage()->once();
        $this->welcome_page_manager->displayWelcomePage($this->project, $this->request);
    }

    public function itDoesNotDisplayTheAlternativeWelcomePageIfALanguageIsDefinedForProject() {
        stub($this->language_manager)->getUsedLanguageForProject($this->project)->returns('en_US');

        expect($this->service)->renderInPage()->never();
        $this->welcome_page_manager->displayWelcomePage($this->project, $this->request);
    }
}