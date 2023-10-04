<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Layout\SidebarPromotedItemPresenter;

class Service // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const FAKE_ID_FOR_CREATION = -1;

    public const SUMMARY   = 'summary';
    public const ADMIN     = 'admin';
    public const FORUM     = 'forum';
    public const HOMEPAGE  = 'homepage';
    public const NEWS      = 'news';
    public const FILE      = 'file';
    public const SVN       = 'svn';
    public const WIKI      = 'wiki';
    public const TRACKERV3 = 'tracker';

    public const SCOPE_SYSTEM  = 'system';
    public const SCOPE_PROJECT = 'project';

    public const ICONS = [
        self::ADMIN     => 'fas fa-cogs',
        self::FORUM     => 'fas fa-users',
        self::HOMEPAGE  => 'fas fa-home',
        self::NEWS      => 'fas fa-rss',
        self::WIKI      => 'fas fa-tlp-wiki',
        self::TRACKERV3 => 'fas fa-list-ol',
    ];

    /**
     * @var array{
     *          service_id: int,
     *          group_id: int,
     *          label: string,
     *          description: string,
     *          short_name: string,
     *          link: string,
     *          is_active: int,
     *          is_used: int,
     *          scope: string,
     *          rank: int,
     *          location: string,
     *          server_id: ?int,
     *          is_in_iframe: int,
     *          is_in_new_tab: bool,
     *          icon: string
     *       }
     */
    public $data;

    /**
     * @var Project
     */
    public $project;


    /**
     * Create an instance of Service
     *
     * @param Project $project The project the service belongs to
     * @param array   $data    The service data coming from the db
     *
     * @throws ServiceNotAllowedForProjectException if the Service is not allowed for the project (mainly for plugins)
     */
    public function __construct(Project $project, array $data)
    {
        if (! $this->isAllowed($project)) {
            throw new ServiceNotAllowedForProjectException();
        }
        $this->project = $project;
        $this->data    = $data;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getGroupId(): int
    {
        return (int) $this->data['group_id'];
    }

    public function getId(): int
    {
        return (int) $this->data['service_id'];
    }

    public function getDescription(): string
    {
        return $this->data['description'];
    }

    public function getShortName(): string
    {
        return $this->data['short_name'];
    }

    public function getLabel(): string
    {
        return $this->data['label'];
    }

    public function getRank(): int
    {
        return (int) $this->data['rank'];
    }

    public function isUsed(): bool
    {
        return (bool) $this->data['is_used'];
    }

    public function isActive(): bool
    {
        return (bool) $this->data['is_active'];
    }

    public function isIFrame(): bool
    {
        return (bool) $this->data['is_in_iframe'];
    }

    public function getUrl(?string $url = null): string
    {
        if ($url) {
            return $url;
        }
        return $this->data['link'] ?? '#';
    }

    /**
     * By default, url should be able to change to keep the compatibility with old services that rely on DB values for
     * their URLs.
     *
     * Plus it's not safe to make all services with dedicated \Service sub class not URL modifiable because we know there
     * are usage of this trick in the wild and this would generate massive regression.
     */
    public function urlCanChange(): bool
    {
        return true;
    }

    public function getScope(): string
    {
        return $this->data['scope'];
    }

    /**
     * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
     */
    public function isAbsolute(string $url): bool
    {
        $components = [];
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i', $url, $components);
        return (isset($components[1]) && $components[1]);
    }

    public function getPublicArea(): string
    {
        return '';
    }

    public function displayHeader(string $title, $breadcrumbs, array $toolbar, array $params = []): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment(strtolower($this->getShortName()));

        $GLOBALS['HTML']->setRenderedThroughService(true);
        $GLOBALS['HTML']->addBreadcrumbs($breadcrumbs);

        foreach ($toolbar as $t) {
            $class      = isset($t['class']) ? 'class="' . $t['class'] . '"' : '';
            $item_title = isset($t['short_title']) ? $t['short_title'] : $t['title'];
            $data_test  = isset($t['data-test']) ? 'data-test="' . $t['data-test'] . '"' : '';
            $GLOBALS['HTML']->addToolbarItem('<a href="' . $t['url'] . '" ' . $class . ' ' . $data_test . '>' . $item_title . '</a>');
        }

        $pv = (int) HTTPRequest::instance()->get('pv');
        if (empty($params)) {
            $params = \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                ->inProject($this->project, (string) $this->getId())
                ->withBodyClass(['service-' . $this->getShortName()])
                ->withPrinterVersion($pv)
                ->build();
        } else {
            $params['title']  = $title;
            $params['toptab'] = $this->getId();

            if (! isset($params['body_class'])) {
                $params['body_class'] = [];
            }
            $params['body_class'][] = 'service-' . $this->getShortName();
            if ($pv) {
                $params['pv'] = $pv;
            }
        }

        $this->displayDuplicateInheritanceWarning();

        site_project_header($this->project, $params);
    }

    /**
     * Display a warning if the service configuration is not inherited on project creation
     */
    public function displayDuplicateInheritanceWarning(): void
    {
        if ($this->project->isTemplate() && ! $this->isInheritedOnDuplicate()) {
            $GLOBALS['HTML']->addFeedback('warning', $GLOBALS['Language']->getText('global', 'service_conf_not_inherited'));
        }
    }

    public function displayFooter(): void
    {
        $params = [
            'project' => $this->project,
        ];
        if ($pv = (int) HTTPRequest::instance()->get('pv')) {
            $params['pv'] = (int) $pv;
        }
        site_project_footer($params);
    }

    public function isOpenedInNewTab(): bool
    {
        return false;
    }

    protected function isAllowed(Project $project): bool
    {
        return true;
    }

    public function isRestricted(): bool
    {
        return false;
    }

    /**
     * Return true if service configuration is inherited on clone
     */
    public function isInheritedOnDuplicate(): bool
    {
        return false;
    }

    public function getInternationalizedName(): string
    {
        $label      = $this->getLabel();
        $short_name = $this->getShortName();

        return $this->getInternationalizedText($label, "service_{$short_name}_lbl_key");
    }

    public function getProjectAdministrationName(): string
    {
        return $this->getInternationalizedName();
    }

    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();
        $short_name  = $this->getShortName();

        return $this->getInternationalizedText($description, "service_{$short_name}_desc_key");
    }

    private function getInternationalizedText($text, $key): string
    {
        if ($text === $key) {
            return $GLOBALS['Language']->getOverridableText('project_admin_editservice', $key);
        }

        if (preg_match('/(.*):(.*)/', $text, $matches)) {
            if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                $text = $GLOBALS['Language']->getOverridableText($matches[1], $matches[2]);
            }
        }

        return $text;
    }

    public function getIcon(): string
    {
        $icon_name = $this->getIconName();
        if ($icon_name !== "") {
            return $this->getFontAwesomeIcon($icon_name);
        }
        throw new RuntimeException('Regular services must provide an icon');
    }

    /**
     * @throws RuntimeException
     */
    public function getIconName(): string
    {
        return self::ICONS[$this->getShortName()] ?? "";
    }

    private function getFontAwesomeIcon(string $icon): string
    {
        $font_awesome_ref = \Tuleap\Project\Service\ServiceIconValidator::getFontAwesomeIconFromID($icon);
        if ($font_awesome_ref !== null) {
            return 'fa-fw ' . $font_awesome_ref;
        }

        return 'fa-fw ' . $icon;
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getPromotedItemPresenters(PFUser $user, ?string $active_promoted_item_id): array
    {
        return [];
    }
}
