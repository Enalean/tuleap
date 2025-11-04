<?php
/*
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\PHPWiki\WikiPage;

function exit_wiki_empty()
{
    global $HTML;
    global $group_id;

    $pm    = ProjectManager::instance();
    $go    = $pm->getProject($group_id);
    $uname = $go->getUnixName();

    $HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($GLOBALS['Language']->getText('wiki_views_wikiviews', 'title_error')));

    print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'not_activate', [$uname]);

    $HTML->footer([]);
    exit;
}

/**
 * Generate url to Expend/Collapse a part of a page
 * @see my_hide_url
 */
function hide_url($svc, $db_item_id, $defaultHide = false, $hide = null)
{
    $pref_name = 'hide_' . $svc . $db_item_id;

    if (empty($hide)) {
        if (isset($_REQUEST['hide_' . $svc])) {
            $hide = $_REQUEST['hide_' . $svc];
        }
    }

    $noPref   = false;
    $old_hide = user_get_preference($pref_name);

  // Make sure they are both 0 if never set before
    if ($old_hide == false) {
        $noPref   = true;
        $old_hide = 0;
    }

  // If no given value for hide, keep the old one
    if (! isset($hide)) {
        $hide = $old_hide;
    }

  // Update pref value if needed
    if ($old_hide != $hide) {
        user_set_preference($pref_name, $hide);
    }

    if ($hide == 2 || ($noPref && $defaultHide)) {
        $hide_url = 'hide_' . $svc . '=1&hide_item_id=' . $db_item_id;
        $hide_img = '<img src="' . util_get_image_theme('pointer_right.png') . '" align="middle" border="0" alt="Expand">';
        $hide_now = true;
    } else {
        $hide_url = 'hide_' . $svc . '=2&hide_item_id=' . $db_item_id;
        $hide_img = '<img src="' . util_get_image_theme('pointer_down.png') . '" align="middle" border="0" alt="Collapse">';
        $hide_now = false;
    }

    return [$hide_now, $hide_url, $hide_img];
}

function wiki_display_header()
{
    $GLOBALS['wiki_view']->header();
    echo '<div class="tlp-card" data-test="main-content">';
}

function wiki_display_footer()
{
    echo '</div>';
    $GLOBALS['wiki_view']->footer();
}

abstract class WikiViews extends Views // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    #[FeatureFlagConfigKey('Feature flag to remove PhpWiki deperecation message')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const string FEATURE_FLAG_REMOVE_DEPRECATION_MESSAGE = 'remove_php_wiki_deprecation_message';

    public int $gid;
    public string $wikiname;
    public string $wikiLink;
    public string $wikiAdminLink;
    protected string $title;

    public function __construct(&$controler, $id = 0, $view = null)
    {
        parent::view($controler, $view);

        $this->gid = (int) $id;

      // Parameters for HTML rendering

      // Wikize project name
        $pm             = ProjectManager::instance();
        $go             = $pm->getProject($this->gid);
        $this->wikiname = ucfirst($go->getUnixName()) . 'Wiki';

      // Build convenients URL
        $this->wikiLink      = '/wiki/index.php?group_id=' . $this->gid;
        $this->wikiAdminLink = '/wiki/admin/index.php?group_id=' . $this->gid;
    }

    protected function getServiceCrumb(): BreadCrumb
    {
        $service_crumb = new BreadCrumb(
            new BreadCrumbLink(
                _('Wiki'),
                '/wiki/?group_id=' . $this->gid,
            )
        );
        if (user_ismember($this->gid, 'W2') || user_ismember($this->gid, 'A')) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                $GLOBALS['Language']->getText('global', 'Administration'),
                                $this->wikiAdminLink
                            )->setDataAttribute('test', 'wiki-admin'),
                        ]
                    )
                )
            );
            $service_crumb->setSubItems($sub_items);
        }

        return $service_crumb;
    }

    protected function renderPerms($postUrl = ''): void
    {
        $wp       = new WikiPage($_REQUEST['id']);
        $pagename = $wp->getPagename();

        $eM         = EventManager::instance();
        $referenced = false;
        $eM->processEvent('isWikiPageReferenced', [
            'referenced' => &$referenced,
            'wiki_page'  => $pagename,
            'group_id' => $this->gid,
        ]);
        echo '
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                         <h1 class="tlp-pane-title">' . $GLOBALS['Language']->getText('wiki_views_wikiviews', 'set_perm_title') . '</h1>
                    </div>
                    <div class="tlp-pane-section">';

        if ($referenced) {
            $label = '';
            $eM->processEvent('getPermsLabelForWiki', [
                'label'  => &$label,
            ]);
            print '<div class="tlp-alert-info">' . $label . '</div>';
        } else {
            if (empty($pagename)) {
                print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'empty_page');
            } else {
                $purifier = Codendi_HTMLPurifier::instance();
                print $GLOBALS['Language']->getText('wiki_views_wikiviews', 'not_empty_page', [$purifier->purify($pagename)]);
                permission_display_selection_form('WIKIPAGE_READ', $wp->getId(), $this->gid, $postUrl);
            }
        }

        echo '</div>
            </div>
        </section>';
    }
}
