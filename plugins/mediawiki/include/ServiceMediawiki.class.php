<?php

use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;

class ServiceMediawiki extends Service {

    public function renderInPage($title, $template, $presenter = null)
    {
        $this->displayHeader($title);

        if ($presenter) {
            $this->getRenderer()->renderToPage($template, $presenter);
        }

        $this->displayFooter();
        exit;
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(dirname(MEDIAWIKI_BASE_DIR).'/templates');
    }

    public function displayHeader($title, $breadcrumbs = [], $toolbar = [], $params = [])
    {
        if ($this->userIsAdmin(UserManager::instance()->getCurrentUser())) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Administration'),
                'url'   => MEDIAWIKI_BASE_URL .'/forge_admin.php?'. http_build_query(array(
                    'group_id'   => $this->project->getID(),
                ))
            );
        }

        $title       = $title.' - '.$GLOBALS['Language']->getText('plugin_mediawiki', 'service_lbl_key');
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public function userIsAdmin(PFUser $user) {
        $forge_user_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
        $has_special_permission = $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );

        return $has_special_permission || $user->isMember($this->project->getID(), 'A');
    }
}
