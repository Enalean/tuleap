<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2009. All rights reserved
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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\Widget\WidgetEmbeddedDao;
use Tuleap\Project\MappingRegistry;

/**
 * Embed an item in the dashboard
 * - Display the content of embedded documents
 * - Display the image of "image" file documents
 * - else display a link to the item
 *
 * The display of a folder (its children) would be great
 */
class Docman_Widget_Embedded extends Widget implements \Tuleap\Docman\Item\ItemVisitor
{
    /**
     * The title given by the user to the widget
     *
     * @var string|null
     */
    protected $plugin_docman_widget_embedded_title;

    /**
     * The item id to display
     *
     * @var int|null
     */
    protected $plugin_docman_widget_embedded_item_id;

    /**
     * The path to this plugin
     * @var string
     */
    protected $plugin_path;

    /**
     * Constructor
     * @param string $id the internal identifier of the widget (plugin_docman_my_embedded | plugin_docman_project_embedded)
     * @param int $owner_id the id of the owner (user id, group id, ...)
     * @param string $owner_type the type of the owner
     * @param string $plugin_path the path of the plugin to build urls
     */
    public function __construct($id, $owner_id, $owner_type, $plugin_path)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
        $this->plugin_path = $plugin_path;
    }

    /**
     * Get the title of the widget. Default is 'Embedded Document'
     * Else it is the title given by the user
     * @return string
     */
    public function getTitle()
    {
        return $this->plugin_docman_widget_embedded_title ?:
               dgettext('tuleap-docman', 'Document viewer');
    }

    /**
     * Compute the content of the widget
     * @return string html
     */
    public function getContent()
    {
        if (! $this->plugin_docman_widget_embedded_item_id) {
            return '';
        }

        $item = $this->getItem($this->plugin_docman_widget_embedded_item_id);
        if ($item) {
            $project = ProjectManager::instance()->getProject((int) $item->getGroupId());
            $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
            if ($service instanceof \Tuleap\Docman\ServiceDocman) {
                $item_url = $service->getUrl();
                if ($item->getParentId()) {
                    $item_url .= 'preview/' . urlencode((string) $item->getId());
                }

                $content  = '';
                $content .= $item->accept($this);
                $content .= '<div style="text-align:center"><a href="' . $item_url . '">[Go to document]</a></div>';

                return $content;
            }
        }

        return 'Document doesn\'t exist or you don\'t have permissions to see it';
    }

    /**
     * Says if the content of the widget can be displayed through an ajax call
     * If true, then the dashboard will be rendered faster but the page will be a little bit crappy until full load.
     * @return bool
     */
    public function isAjax()
    {
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . $widget_id . '">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="title-' . $widget_id . '"
                       name="plugin_docman_widget_embedded[title]"
                       value="' . $purifier->purify($this->getTitle()) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="item-id-' . $widget_id . '">
                    Item_id <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="item-id-' . $widget_id . '"
                       name="plugin_docman_widget_embedded[item_id]"
                       value="' . $purifier->purify($this->plugin_docman_widget_embedded_item_id) . '"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-docman-embedded-item-id">
                    Item_id <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="widget-docman-embedded-item-id"
                       name="plugin_docman_widget_embedded[item_id]"
                       value="' . $purifier->purify($this->plugin_docman_widget_embedded_item_id) . '"
                       required
                       placeholder="123">
            </div>
            ';
    }

    /**
     * Clone the content of the widget (for templates)
     * @return int the id of the new content
     */
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $dao = new WidgetEmbeddedDao();

        if (! $mapping_registry->hasCustomMapping(\DocmanPlugin::ITEM_MAPPING_KEY)) {
            return $dao->cloneContent(
                (int) $this->owner_id,
                (string) $this->owner_type,
                (int) $owner_id,
                (string) $owner_type
            );
        }

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return $transaction_executor->execute(
            function () use ($id, $dao, $owner_id, $owner_type, $mapping_registry): int {
                $data = $dao->searchContent($this->owner_id, $this->owner_type, (int) $id);
                if (! $data) {
                    return $dao->cloneContent(
                        $this->owner_id,
                        $this->owner_type,
                        (int) $owner_id,
                        (string) $owner_type
                    );
                }

                $item_mapping = $mapping_registry->getCustomMapping(\DocmanPlugin::ITEM_MAPPING_KEY);
                if (! isset($item_mapping[$data['item_id']])) {
                    return $dao->insertContent(
                        (int) $owner_id,
                        (string) $owner_type,
                        $data['title'],
                        $data['item_id'],
                    );
                }

                return $dao->insertContent(
                    (int) $owner_id,
                    (string) $owner_type,
                    $data['title'],
                    $item_mapping[$data['item_id']],
                );
            }
        );
    }

    /**
     * Lazy load the content
     * @param int|string $id the id of the content
     */
    public function loadContent($id)
    {
        $dao  = new WidgetEmbeddedDao();
        $data = $dao->searchContent($this->owner_id, $this->owner_type, (int) $id);
        if ($data) {
            $this->plugin_docman_widget_embedded_title   = $data['title'];
            $this->plugin_docman_widget_embedded_item_id = $data['item_id'];
            $this->content_id                            = (int) $id;
        }
    }

    /**
     * Create a new content for this widget
     * @return int|false the id of the new content
     */
    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vItem_id   = new Valid_String('item_id');
        $vItem_id->setErrorMessage("Unable to add the widget. Please give an item id.");
        $vItem_id->required();
        if ($request->validInArray('plugin_docman_widget_embedded', $vItem_id)) {
            $plugin_docman_widget_embedded = $request->get('plugin_docman_widget_embedded');
            $vTitle                        = new Valid_String('title');
            $vTitle->required();
            if (! $request->validInArray('plugin_docman_widget_embedded', $vTitle)) {
                if ($item = $this->getItem($plugin_docman_widget_embedded['item_id'])) {
                    $plugin_docman_widget_embedded['title'] = $item->getTitle();
                }
            }
            $sql        = 'INSERT INTO plugin_docman_widget_embedded (owner_id, owner_type, title, item_id) VALUES (' . db_ei($this->owner_id) . ", '" . db_es($this->owner_type) . "', '" . db_escape_string($plugin_docman_widget_embedded['title']) . "', '" . db_escape_string($plugin_docman_widget_embedded['item_id']) . "')";
            $res        = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    /**
     * Update the preferences
     * @return bool true if something has been updated
     */
    public function updatePreferences(Codendi_Request $request)
    {
        $done       = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($plugin_docman_widget_embedded = $request->get('plugin_docman_widget_embedded')) && $request->valid($vContentId)) {
            $vItem_id = new Valid_String('item_id');
            if ($request->validInArray('plugin_docman_widget_embedded', $vItem_id)) {
                $item_id = " item_id   = " . db_ei($plugin_docman_widget_embedded['item_id']) . " ";
            } else {
                $item_id = ' item_id = item_id ';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('plugin_docman_widget_embedded', $vTitle)) {
                $title = " title = '" . db_escape_string($plugin_docman_widget_embedded['title']) . "' ";
            } else {
                $title = ' title = title ';
            }

            $sql  = "UPDATE plugin_docman_widget_embedded
                    SET " . $title . ", " . $item_id . "
                    WHERE owner_id   = " . db_ei($this->owner_id) . "
                      AND owner_type = '" . db_es($this->owner_type) . "'
                      AND id         = " . db_ei((int) $request->get('content_id'));
            $res  = db_query($sql);
            $done = true;
        }
        return $done;
    }

    /**
     * The widget has just been removed from the dashboard.
     * We must delete its content.
     * @param int $id the id of the content
     */
    public function destroy($id)
    {
        $sql = 'DELETE FROM plugin_docman_widget_embedded WHERE id = ' . db_ei($id) . ' AND owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "'";
        db_query($sql);
    }

    /**
     * Says if the widget allows (or not) more than one instance on the same dashboard
     * It's up to the widget to decide if it is relevant.
     * @return bool
     */
    public function isUnique()
    {
        return false;
    }

    /**
     * The category of the widget. Override this method if your widget is not in the "general" category.
     * Here are some exemple of categories used by Codendi: forum, frs, scm, trackers + plugin's ones
     * @return string
     */
    public function getCategory()
    {
        return dgettext('tuleap-docman', 'Document manager');
    }

    /**
     * Return an item (we don't know the group_id)
     * @param int $item_id the id of the item to retrieve
     * @return Docman_Item|null
     */
    protected function getItem($item_id)
    {
        $item = null;
        $dao  = new Docman_ItemDao(CodendiDataAccess::instance());
        if ($row = $dao->searchByid($item_id)->getRow()) {
            $item = Docman_ItemFactory::instance($row['group_id'])->getItemFromRow($row);
            $dPm  = Docman_PermissionsManager::instance($row['group_id']);
            $user = UserManager::instance()->getCurrentUser();
            if (! $dPm->userCanRead($user, $item->getId())) {
                $item = null;
            }
        }
        return $item;
    }

    public function visitFolder(Docman_Folder $item, $params = [])
    {
        // do nothing
        return '';
    }

    public function visitDocument($item, $params = [])
    {
        // do nothing
        return '';
    }

    public function visitWiki(Docman_Wiki $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink(Docman_Link $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitFile(Docman_File $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = [])
    {
        $hp      = Codendi_HTMLPurifier::instance();
        $html    = '';
        $version = $item->getCurrentVersion();
        if (file_exists($version->getPath())) {
            $em = EventManager::instance();
            $em->processEvent('plugin_docman_event_access', [
                'group_id' => $item->getGroupId(),
                'item'     => $item,
                'version'  => $version->getNumber(),
                'user'     => UserManager::instance()->getCurrentUser(),
            ]);
            $mime = explode('/', $version->getFiletype());
            if (in_array($mime[1], ['plain', 'css', 'javascript'])) {
                $balise = 'pre';
            } else {
                $balise = 'div';
            }
            $html .= '<' . $balise . ' style="clear:both">';
            $html .= $hp->purify(file_get_contents($version->getPath()), CODENDI_PURIFIER_FULL);
            $html .= '</' . $balise . '>';
        } else {
            $html .= '<em>' . dgettext('tuleap-docman', 'The file cannot be found.') . '</em>';
        }
        return $html;
    }

    public function visitEmpty(Docman_Empty $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return '';
    }

    public function getDescription()
    {
        return dgettext('tuleap-docman', 'Display a docman item directly in the dashboard. <br /><em>For now, only embedded files are supported</em>.');
    }
}
