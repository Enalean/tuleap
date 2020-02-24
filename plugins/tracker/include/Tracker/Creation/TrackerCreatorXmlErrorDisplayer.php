<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Codendi_HTMLPurifier;
use Project;
use XML_ParseError;

class TrackerCreatorXmlErrorDisplayer
{
    /**
     * @var \TrackerManager
     */
    private $tracker_manager;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(\TrackerManager $tracker_manager, Codendi_HTMLPurifier $purifier)
    {
        $this->tracker_manager = $tracker_manager;
        $this->purifier        = $purifier;
    }

    public static function build(): self
    {
        return new self(
            new \TrackerManager(),
            Codendi_HTMLPurifier::instance()
        );
    }

    public function displayErrors(Project $project, array $parse_errors, array $xml_file): void
    {
        $breadcrumbs = [
            [
                'title' => 'Create a new tracker',
                'url'   => TRACKER_BASE_URL . '/?group_id=' . urlencode($project->group_id) . '&amp;func=create'
            ]
        ];
        $toolbar     = [];
        $params      = [];

        $this->tracker_manager->displayHeader($project, 'Trackers', $breadcrumbs, $toolbar, $params);
        echo '<h2>XML file doesnt have correct format</h2>';

        $errors = $this->buildErrors($parse_errors);

        echo $this->buildErrorLineDiff($xml_file, $errors);
        $this->tracker_manager->displayFooter($project);
        exit;
    }

    /**
     * protected for testing purpose
     */
    protected function buildErrorLineDiff(array $xml_file, array $errors): string
    {
        $clear = $this->retrieveClearImage();
        $icons  = $this->retrieverErrorIcon();

        $styles = [
            'error' => 'color:red; font-weight:bold;',
        ];

        $string = '<pre>';
        foreach ($xml_file as $number => $line) {
            $next_line = (int) $number + 1;
            $string .= '<div id="line_' . ($next_line) . '">';
            $string .= '<span style="color:gray;">' . sprintf('%4d', $next_line) . '</span>' .
                $clear . $this->purifier->purify($line, CODENDI_PURIFIER_CONVERT_HTML);
            if (isset($errors[$next_line])) {
                foreach ($errors[$next_line] as $column => $errors) {
                    $string .= '<div>' . sprintf('%3s', '') . $clear .
                        sprintf('%' . ($column - 1) . 's', '') .
                        '<span style="color:blue; font-weight:bold;">^</span></div>';
                    foreach ($errors as $parse_error) {
                        $style = isset($styles['error']) ? $styles['error'] : '';
                        $string .= '<div style="' . $style . '">';
                        if (isset($icons[$parse_error->getType()])) {
                            $string .= $icons[$parse_error->getType()];
                        } else {
                            $string .= $clear;
                        }
                        $string .= sprintf('%3s', '') . sprintf('%' . ($column - 1) . 's', '') . $parse_error->getMessage();
                        $string .= '</div>';
                    }
                }
            }
            $string .= '</div>';
        }
        $string .= '</pre>';

        return $string;
    }

    /**
     * protected for testing purpose
     */
    protected function buildErrors(array $parse_errors): array
    {
        $errors = [];
        foreach ($parse_errors as $error) {
            /** @var XML_ParseError $error */
            $errors[$error->getLine()][$error->getColumn()][] = $error;
        }

        return $errors;
    }

    /**
     * protected for testing purpose
     */
    protected function retrieveClearImage()
    {
        return $GLOBALS['HTML']->getimage('clear.png', ['width' => 24, 'height' => 1]);
    }

    /**
     * protected for testing purpose
     */
    protected function retrieverErrorIcon(): array
    {
        return [
            'error' => $GLOBALS['HTML']->getimage('ic/error.png', ['style' => 'vertical-align:middle']),
        ];
    }
}
