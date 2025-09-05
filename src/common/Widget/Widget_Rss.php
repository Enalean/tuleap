<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi 2001-2009.
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

use Laminas\Feed\Reader\Entry\EntryInterface;
use Tuleap\Date\DateHelper;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\MappingRegistry;
use Tuleap\RSS\FeedHTTPClient;
use Laminas\Feed\Reader\Reader as FeedReader;

/**
* Widget_Rss
*
* Rss reader
*/
abstract class Widget_Rss extends Widget // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public ?string $rss_title = null;
    public ?string $rss_url   = null;

    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    #[\Override]
    public function getTitle()
    {
        return $this->rss_title ?: 'RSS Reader';
    }

    #[\Override]
    public function getContent(): string
    {
        if (! $this->rss_url) {
            return '';
        }
        $hp      = Codendi_HTMLPurifier::instance();
        $content = '<table class="tlp-table">';
        try {
            $feed = $this->retrieveFeed($this->rss_url);
        } catch (Laminas\Feed\Exception\RuntimeException $ex) {
            return '<div class="tlp-alert-warning">' . _('An issue occurred while retrieving the RSS feed') . '</div>' .  $content . '</table>';
        }

        $feed_entries = \Psl\Dict\sort(
            $feed,
            fn (EntryInterface $a, EntryInterface $b): int => $b->getDateModified() <=> $a->getDateModified(),
        );

        $uri_sanitizer = new \Tuleap\Sanitizer\URISanitizer(new Valid_HTTPURI());

        foreach (\Psl\Dict\slice($feed_entries, 0, 10) as $entry) {
            $content      .= '<tr><td>';
            $entry_content = $hp->purify($entry->getTitle(), CODENDI_PURIFIER_STRIP_HTML);
            $link          = $entry->getPermalink();
            if ($link !== null) {
                $entry_content = '<a href="' . $hp->purify($uri_sanitizer->sanitizeForHTMLAttribute($link)) . '">' . $entry_content . '</a>';
            }
            $content .= $entry_content;
            $date     = $entry->getDateCreated();
            if ($date !== null) {
                $content .= '<span style="color:#999;" title="' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $date->getTimestamp()) . '"> - ' . DateHelper::timeAgoInWords($date->getTimestamp()) . '</span>';
            }
            $content .= '</td></tr>';
        }

        return $content . '</table>';
    }

    #[\Override]
    public function isAjax()
    {
        return true;
    }

    #[\Override]
    public function hasPreferences($widget_id)
    {
        return true;
    }

    #[\Override]
    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . $widget_id . '">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="title-' . $widget_id . '"
                       name="rss[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="RSS">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="url-' . $widget_id . '">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="url-' . $widget_id . '"
                       name="rss[url]"
                       value="' . $purifier->purify($this->rss_url) . '"
                       pattern="https?://.*"
                       title="' . $purifier->purify(_('Please, enter a http:// or https:// link')) . '"
                       required
                       placeholder="https://example.com/rss.xml">
            </div>
            ';
    }

    #[\Override]
    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-rss-title">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="widget-rss-title"
                       name="rss[title]"
                       value="' . $purifier->purify($this->getTitle()) . '"
                       placeholder="RSS">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-rss-url">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="widget-rss-url"
                       name="rss[url]"
                       pattern="https?://.*"
                       title="' . $purifier->purify(_('Please, enter a http:// or https:// link')) . '"
                       required
                       placeholder="https://example.com/rss.xml">
            </div>
            ';
    }

    #[\Override]
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $sql = 'INSERT INTO widget_rss (owner_id, owner_type, title, url)
        SELECT  ' . db_ei($owner_id) . ", '" . db_es($owner_type) . "', title, url
        FROM widget_rss
        WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' ";
        $res = db_query($sql);
        return db_insertid($res);
    }

    #[\Override]
    public function loadContent($id)
    {
        $sql = 'SELECT * FROM widget_rss WHERE owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_es($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data             = db_fetch_array($res);
            $this->rss_title  = $data['title'];
            $this->rss_url    = $data['url'];
            $this->content_id = $id;
        }
    }

    #[\Override]
    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vUrl       = new Valid_String('url');
        $vUrl->setErrorMessage("Can't add empty rss url");
        $vUrl->required();
        if ($request->validInArray('rss', $vUrl)) {
            $rss    = $request->get('rss');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (! $request->validInArray('rss', $vTitle)) {
                try {
                    $feed         = $this->retrieveFeed($rss['url']);
                    $rss['title'] = $feed->getTitle();
                } catch (\Laminas\Feed\Exception\RuntimeException $ex) {
                    $rss['title'] = $request->get('title');
                }
            }
            $sql        = 'INSERT INTO widget_rss (owner_id, owner_type, title, url) VALUES (' . db_ei($this->owner_id) . ", '" . db_es($this->owner_type) . "', '" . db_escape_string($rss['title']) . "', '" . db_escape_string($rss['url']) . "')";
            $res        = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    #[\Override]
    public function updatePreferences(Codendi_Request $request)
    {
        $done       = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($rss = $request->get('rss')) && $request->valid($vContentId)) {
            $vUrl = new Valid_String('url');
            if ($request->validInArray('rss', $vUrl)) {
                $url = " url   = '" . db_escape_string($rss['url']) . "' ";
            } else {
                $url = '';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('rss', $vTitle)) {
                $title = " title = '" . db_escape_string($rss['title']) . "' ";
            } else {
                $title = '';
            }

            if ($url || $title) {
                $sql  = 'UPDATE widget_rss SET ' . $title . ', ' . $url . ' WHERE owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei((int) $request->get('content_id'));
                $res  = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }

    #[\Override]
    public function destroy($id)
    {
        $sql = 'DELETE FROM widget_rss WHERE id = ' . db_ei($id) . ' AND owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "'";
        db_query($sql);
    }

    #[\Override]
    public function isUnique()
    {
        return false;
    }

    /**
     * @throws \Laminas\Feed\Exception\RuntimeException
     */
    private function retrieveFeed(string $url): \Laminas\Feed\Reader\Feed\FeedInterface
    {
        $http_client = new FeedHTTPClient(HttpClientFactory::createClient(), HTTPFactoryBuilder::requestFactory());
        FeedReader::setHttpClient($http_client);
        $cache_dir = ForgeConfig::get('codendi_cache_dir') . '/rss';
        if (! is_dir($cache_dir) && ! mkdir($cache_dir) && ! is_dir($cache_dir)) {
            throw new \RuntimeException(sprintf('RSS cache directory "%s" was not created', $cache_dir));
        }
        /** @psalm-var \Laminas\Cache\Storage\StorageInterface<Laminas\Cache\Storage\Adapter\AdapterOptions> $cache */
        $cache = new Laminas\Cache\Storage\Adapter\Filesystem(['cache_dir' => $cache_dir]);
        FeedReader::setCache($cache);
        FeedReader::useHttpConditionalGet();

        return FeedReader::import($url);
    }
}
