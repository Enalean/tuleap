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
 */

declare(strict_types=1);

namespace Tuleap\Project\Service;

class ServiceIconValidator
{
    private const ALLOWED_ICONS =  [
        'fa-glass' =>
             [
                 'fa-icon' => 'fas fa-glass-martini',
                 'description' => 'Glass',
             ],
        'fa-music' =>
             [
                 'fa-icon' => 'fas fa-music',
                 'description' => 'Music',
             ],
        'fa-heart' =>
             [
                 'fa-icon' => 'fas fa-heart',
                 'description' => 'Heart',
             ],
        'fa-star' =>
             [
                 'fa-icon' => 'fas fa-star',
                 'description' => 'Star',
             ],
        'fa-star-o' =>
             [
                 'fa-icon' => 'far fa-star',
                 'description' => 'Star Outlined',
             ],
        'fa-film' =>
             [
                 'fa-icon' => 'fas fa-film',
                 'description' => 'Film',
             ],
        'fa-th-large' =>
             [
                 'fa-icon' => 'fas fa-th-large',
                 'description' => 'Th-large',
             ],
        'fa-th' =>
             [
                 'fa-icon' => 'fas fa-th',
                 'description' => 'Th',
             ],
        'fa-th-list' =>
             [
                 'fa-icon' => 'fas fa-th-list',
                 'description' => 'Th-list',
             ],
        'fa-times' =>
             [
                 'fa-icon' => 'fas fa-times',
                 'description' => 'Times',
             ],
        'fa-search-plus' =>
             [
                 'fa-icon' => 'fas fa-search-plus',
                 'description' => 'Search Plus',
             ],
        'fa-search-minus' =>
             [
                 'fa-icon' => 'fas fa-search-minus',
                 'description' => 'Search Minus',
             ],
        'fa-signal' =>
             [
                 'fa-icon' => 'fas fa-signal',
                 'description' => 'Signal',
             ],
        'fa-trash-o' =>
             [
                 'fa-icon' => 'far fa-trash-alt',
                 'description' => 'Trash Outlined',
             ],
        'fa-road' =>
             [
                 'fa-icon' => 'fas fa-road',
                 'description' => 'Road',
             ],
        'fa-download' =>
             [
                 'fa-icon' => 'fas fa-download',
                 'description' => 'Download',
             ],
        'fa-arrow-circle-o-down' =>
             [
                 'fa-icon' => 'far fa-arrow-alt-circle-down',
                 'description' => 'Arrow Circle Outlined Down',
             ],
        'fa-arrow-circle-o-up' =>
             [
                 'fa-icon' => 'far fa-arrow-alt-circle-up',
                 'description' => 'Arrow Circle Outlined Up',
             ],
        'fa-play-circle-o' =>
             [
                 'fa-icon' => 'far fa-play-circle',
                 'description' => 'Play Circle Outlined',
             ],
        'fa-repeat' =>
             [
                 'fa-icon' => 'fas fa-redo',
                 'description' => 'Repeat',
             ],
        'fa-list-alt' =>
             [
                 'fa-icon' => 'fas fa-list-alt',
                 'description' => 'List-alt',
             ],
        'fa-flag' =>
             [
                 'fa-icon' => 'fas fa-flag',
                 'description' => 'Flag',
             ],
        'fa-headphones' =>
             [
                 'fa-icon' => 'fas fa-headphones',
                 'description' => 'Headphones',
             ],
        'fa-volume-off' =>
             [
                 'fa-icon' => 'fas fa-volume-off',
                 'description' => 'Volume-off',
             ],
        'fa-volume-down' =>
             [
                 'fa-icon' => 'fas fa-volume-down',
                 'description' => 'Volume-down',
             ],
        'fa-volume-up' =>
             [
                 'fa-icon' => 'fas fa-volume-up',
                 'description' => 'Volume-up',
             ],
        'fa-qrcode' =>
             [
                 'fa-icon' => 'fas fa-qrcode',
                 'description' => 'Qrcode',
             ],
        'fa-barcode' =>
             [
                 'fa-icon' => 'fas fa-barcode',
                 'description' => 'Barcode',
             ],
        'fa-book' =>
             [
                 'fa-icon' => 'fas fa-book',
                 'description' => 'Book',
             ],
        'fa-print' =>
             [
                 'fa-icon' => 'fas fa-print',
                 'description' => 'Print',
             ],
        'fa-camera' =>
             [
                 'fa-icon' => 'fas fa-camera',
                 'description' => 'Camera',
             ],
        'fa-font' =>
             [
                 'fa-icon' => 'fas fa-font',
                 'description' => 'Font',
             ],
        'fa-bold' =>
             [
                 'fa-icon' => 'fas fa-bold',
                 'description' => 'Bold',
             ],
        'fa-italic' =>
             [
                 'fa-icon' => 'fas fa-italic',
                 'description' => 'Italic',
             ],
        'fa-text-height' =>
             [
                 'fa-icon' => 'fas fa-text-height',
                 'description' => 'Text-height',
             ],
        'fa-text-width' =>
             [
                 'fa-icon' => 'fas fa-text-width',
                 'description' => 'Text-width',
             ],
        'fa-align-left' =>
             [
                 'fa-icon' => 'fas fa-align-left',
                 'description' => 'Align-left',
             ],
        'fa-align-center' =>
             [
                 'fa-icon' => 'fas fa-align-center',
                 'description' => 'Align-center',
             ],
        'fa-align-right' =>
             [
                 'fa-icon' => 'fas fa-align-right',
                 'description' => 'Align-right',
             ],
        'fa-align-justify' =>
             [
                 'fa-icon' => 'fas fa-align-justify',
                 'description' => 'Align-justify',
             ],
        'fa-list' =>
             [
                 'fa-icon' => 'fas fa-list',
                 'description' => 'List',
             ],
        'fa-outdent' =>
             [
                 'fa-icon' => 'fas fa-outdent',
                 'description' => 'Outdent',
             ],
        'fa-indent' =>
             [
                 'fa-icon' => 'fas fa-indent',
                 'description' => 'Indent',
             ],
        'fa-video-camera' =>
             [
                 'fa-icon' => 'fas fa-video',
                 'description' => 'Video Camera',
             ],
        'fa-picture-o' =>
             [
                 'fa-icon' => 'far fa-file-image',
                 'description' => 'Picture Outlined',
             ],
        'fa-pencil' =>
             [
                 'fa-icon' => 'fas fa-pencil-alt',
                 'description' => 'Pencil',
             ],
        'fa-map-marker' =>
             [
                 'fa-icon' => 'fas fa-map-marker',
                 'description' => 'Map-marker',
             ],
        'fa-adjust' =>
             [
                 'fa-icon' => 'fas fa-adjust',
                 'description' => 'Adjust',
             ],
        'fa-tint' =>
             [
                 'fa-icon' => 'fas fa-tint',
                 'description' => 'Tint',
             ],
        'fa-pencil-square-o' =>
             [
                 'fa-icon' => 'far fa-edit',
                 'description' => 'Pencil Square Outlined',
             ],
        'fa-check-square-o' =>
             [
                 'fa-icon' => 'far fa-check-square',
                 'description' => 'Check Square Outlined',
             ],
        'fa-arrows' =>
             [
                 'fa-icon' => 'fas fa-arrows-alt',
                 'description' => 'Arrows',
             ],
        'fa-step-backward' =>
             [
                 'fa-icon' => 'fas fa-step-backward',
                 'description' => 'Step-backward',
             ],
        'fa-fast-backward' =>
             [
                 'fa-icon' => 'fas fa-fast-backward',
                 'description' => 'Fast-backward',
             ],
        'fa-backward' =>
             [
                 'fa-icon' => 'fas fa-backward',
                 'description' => 'Backward',
             ],
        'fa-play' =>
             [
                 'fa-icon' => 'fas fa-play',
                 'description' => 'Play',
             ],
        'fa-pause' =>
             [
                 'fa-icon' => 'fas fa-pause',
                 'description' => 'Pause',
             ],
        'fa-stop' =>
             [
                 'fa-icon' => 'fas fa-stop',
                 'description' => 'Stop',
             ],
        'fa-forward' =>
             [
                 'fa-icon' => 'fas fa-forward',
                 'description' => 'Forward',
             ],
        'fa-fast-forward' =>
             [
                 'fa-icon' => 'fas fa-fast-forward',
                 'description' => 'Fast-forward',
             ],
        'fa-step-forward' =>
             [
                 'fa-icon' => 'fas fa-step-forward',
                 'description' => 'Step-forward',
             ],
        'fa-eject' =>
             [
                 'fa-icon' => 'fas fa-eject',
                 'description' => 'Eject',
             ],
        'fa-chevron-left' =>
             [
                 'fa-icon' => 'fas fa-chevron-left',
                 'description' => 'Chevron-left',
             ],
        'fa-chevron-right' =>
             [
                 'fa-icon' => 'fas fa-chevron-right',
                 'description' => 'Chevron-right',
             ],
        'fa-plus-circle' =>
             [
                 'fa-icon' => 'fas fa-plus-circle',
                 'description' => 'Plus Circle',
             ],
        'fa-minus-circle' =>
             [
                 'fa-icon' => 'fas fa-minus-circle',
                 'description' => 'Minus Circle',
             ],
        'fa-times-circle' =>
             [
                 'fa-icon' => 'fas fa-times-circle',
                 'description' => 'Times Circle',
             ],
        'fa-check-circle' =>
             [
                 'fa-icon' => 'fas fa-check-circle',
                 'description' => 'Check Circle',
             ],
        'fa-question-circle' =>
             [
                 'fa-icon' => 'fas fa-question-circle',
                 'description' => 'Question Circle',
             ],
        'fa-info-circle' =>
             [
                 'fa-icon' => 'fas fa-info-circle',
                 'description' => 'Info Circle',
             ],
        'fa-crosshairs' =>
             [
                 'fa-icon' => 'fas fa-crosshairs',
                 'description' => 'Crosshairs',
             ],
        'fa-times-circle-o' =>
             [
                 'fa-icon' => 'far fa-times-circle',
                 'description' => 'Times Circle Outlined',
             ],
        'fa-check-circle-o' =>
             [
                 'fa-icon' => 'far fa-check-circle',
                 'description' => 'Check Circle Outlined',
             ],
        'fa-ban' =>
             [
                 'fa-icon' => 'fas fa-ban',
                 'description' => 'Ban',
             ],
        'fa-arrow-left' =>
             [
                 'fa-icon' => 'fas fa-arrow-left',
                 'description' => 'Arrow-left',
             ],
        'fa-arrow-right' =>
             [
                 'fa-icon' => 'fas fa-arrow-right',
                 'description' => 'Arrow-right',
             ],
        'fa-arrow-up' =>
             [
                 'fa-icon' => 'fas fa-arrow-up',
                 'description' => 'Arrow-up',
             ],
        'fa-arrow-down' =>
             [
                 'fa-icon' => 'fas fa-arrow-down',
                 'description' => 'Arrow-down',
             ],
        'fa-expand' =>
             [
                 'fa-icon' => 'fas fa-expand',
                 'description' => 'Expand',
             ],
        'fa-compress' =>
             [
                 'fa-icon' => 'fas fa-compress',
                 'description' => 'Compress',
             ],
        'fa-plus' =>
             [
                 'fa-icon' => 'fas fa-plus',
                 'description' => 'Plus',
             ],
        'fa-minus' =>
             [
                 'fa-icon' => 'fas fa-minus',
                 'description' => 'Minus',
             ],
        'fa-asterisk' =>
             [
                 'fa-icon' => 'fas fa-asterisk',
                 'description' => 'Asterisk',
             ],
        'fa-exclamation-circle' =>
             [
                 'fa-icon' => 'fas fa-exclamation-circle',
                 'description' => 'Exclamation Circle',
             ],
        'fa-gift' =>
             [
                 'fa-icon' => 'fas fa-gift',
                 'description' => 'Gift',
             ],
        'fa-leaf' =>
             [
                 'fa-icon' => 'fas fa-leaf',
                 'description' => 'Leaf',
             ],
        'fa-fire' =>
             [
                 'fa-icon' => 'fas fa-fire',
                 'description' => 'Fire',
             ],
        'fa-eye' =>
             [
                 'fa-icon' => 'fas fa-eye',
                 'description' => 'Eye',
             ],
        'fa-eye-slash' =>
             [
                 'fa-icon' => 'fas fa-eye-slash',
                 'description' => 'Eye Slash',
             ],
        'fa-exclamation-triangle' =>
             [
                 'fa-icon' => 'fas fa-exclamation-triangle',
                 'description' => 'Exclamation Triangle',
             ],
        'fa-plane' =>
             [
                 'fa-icon' => 'fas fa-plane',
                 'description' => 'Plane',
             ],
        'fa-calendar' =>
             [
                 'fa-icon' => 'fas fa-calendar',
                 'description' => 'Calendar',
             ],
        'fa-random' =>
             [
                 'fa-icon' => 'fas fa-random',
                 'description' => 'Random',
             ],
        'fa-comment' =>
             [
                 'fa-icon' => 'fas fa-comment',
                 'description' => 'Comment',
             ],
        'fa-magnet' =>
             [
                 'fa-icon' => 'fas fa-magnet',
                 'description' => 'Magnet',
             ],
        'fa-chevron-up' =>
             [
                 'fa-icon' => 'fas fa-chevron-up',
                 'description' => 'Chevron-up',
             ],
        'fa-chevron-down' =>
             [
                 'fa-icon' => 'fas fa-chevron-down',
                 'description' => 'Chevron-down',
             ],
        'fa-retweet' =>
             [
                 'fa-icon' => 'fas fa-retweet',
                 'description' => 'Retweet',
             ],
        'fa-shopping-cart' =>
             [
                 'fa-icon' => 'fas fa-shopping-cart',
                 'description' => 'Shopping-cart',
             ],
        'fa-arrows-v' =>
             [
                 'fa-icon' => 'fas fa-arrows-alt-v',
                 'description' => 'Arrows Vertical',
             ],
        'fa-arrows-h' =>
             [
                 'fa-icon' => 'fas fa-arrows-alt-h',
                 'description' => 'Arrows Horizontal',
             ],
        'fa-bar-chart' =>
             [
                 'fa-icon' => 'far fa-chart-bar',
                 'description' => 'Bar Chart',
             ],
        'fa-camera-retro' =>
             [
                 'fa-icon' => 'fas fa-camera-retro',
                 'description' => 'Camera-retro',
             ],
        'fa-key' =>
             [
                 'fa-icon' => 'fas fa-key',
                 'description' => 'Key',
             ],
        'fa-comments' =>
             [
                 'fa-icon' => 'fas fa-comments',
                 'description' => 'Comments',
             ],
        'fa-thumbs-o-up' =>
             [
                 'fa-icon' => 'far fa-thumbs-up',
                 'description' => 'Thumbs Up Outlined',
             ],
        'fa-thumbs-o-down' =>
             [
                 'fa-icon' => 'far fa-thumbs-down',
                 'description' => 'Thumbs Down Outlined',
             ],
        'fa-star-half' =>
             [
                 'fa-icon' => 'fas fa-star-half',
                 'description' => 'Star-half',
             ],
        'fa-heart-o' =>
             [
                 'fa-icon' => 'far fa-heart',
                 'description' => 'Heart Outlined',
             ],
        'fa-sign-out' =>
             [
                 'fa-icon' => 'fas fa-sign-out-alt',
                 'description' => 'Sign Out',
             ],
        'fa-thumb-tack' =>
             [
                 'fa-icon' => 'fas fa-thumbtack',
                 'description' => 'Thumb Tack',
             ],
        'fa-external-link' =>
             [
                 'fa-icon' => 'fas fa-external-link-alt',
                 'description' => 'External Link',
             ],
        'fa-sign-in' =>
             [
                 'fa-icon' => 'fas fa-sign-in-alt',
                 'description' => 'Sign In',
             ],
        'fa-trophy' =>
             [
                 'fa-icon' => 'fas fa-trophy',
                 'description' => 'Trophy',
             ],
        'fa-github-square' =>
             [
                 'fa-icon' => 'fab fa-github-square',
                 'description' => 'GitHub Square',
             ],
        'fa-upload' =>
             [
                 'fa-icon' => 'fas fa-upload',
                 'description' => 'Upload',
             ],
        'fa-lemon-o' =>
             [
                 'fa-icon' => 'far fa-lemon',
                 'description' => 'Lemon Outlined',
             ],
        'fa-phone' =>
             [
                 'fa-icon' => 'fas fa-phone',
                 'description' => 'Phone',
             ],
        'fa-square-o' =>
             [
                 'fa-icon' => 'far fa-square',
                 'description' => 'Square Outlined',
             ],
        'fa-phone-square' =>
             [
                 'fa-icon' => 'fas fa-phone-square',
                 'description' => 'Phone Square',
             ],
        'fa-twitter' =>
             [
                 'fa-icon' => 'fab fa-twitter',
                 'description' => 'Twitter',
             ],
        'fa-facebook' =>
             [
                 'fa-icon' => 'fab fa-facebook',
                 'description' => 'Facebook',
             ],
        'fa-github' =>
             [
                 'fa-icon' => 'fab fa-github',
                 'description' => 'GitHub',
             ],
        'fa-credit-card' =>
             [
                 'fa-icon' => 'fas fa-credit-card',
                 'description' => 'Credit-card',
             ],
        'fa-hdd-o' =>
             [
                 'fa-icon' => 'far fa-hdd',
                 'description' => 'HDD',
             ],
        'fa-certificate' =>
             [
                 'fa-icon' => 'fas fa-certificate',
                 'description' => 'Certificate',
             ],
        'fa-hand-o-right' =>
             [
                 'fa-icon' => 'far fa-hand-point-right',
                 'description' => 'Hand Outlined Right',
             ],
        'fa-hand-o-left' =>
             [
                 'fa-icon' => 'far fa-hand-point-left',
                 'description' => 'Hand Outlined Left',
             ],
        'fa-hand-o-up' =>
             [
                 'fa-icon' => 'far fa-hand-point-up',
                 'description' => 'Hand Outlined Up',
             ],
        'fa-hand-o-down' =>
             [
                 'fa-icon' => 'far fa-hand-point-down',
                 'description' => 'Hand Outlined Down',
             ],
        'fa-arrow-circle-left' =>
             [
                 'fa-icon' => 'fas fa-arrow-circle-left',
                 'description' => 'Arrow Circle Left',
             ],
        'fa-arrow-circle-right' =>
             [
                 'fa-icon' => 'fas fa-arrow-circle-right',
                 'description' => 'Arrow Circle Right',
             ],
        'fa-arrow-circle-up' =>
             [
                 'fa-icon' => 'fas fa-arrow-circle-up',
                 'description' => 'Arrow Circle Up',
             ],
        'fa-arrow-circle-down' =>
             [
                 'fa-icon' => 'fas fa-arrow-circle-down',
                 'description' => 'Arrow Circle Down',
             ],
        'fa-globe' =>
             [
                 'fa-icon' => 'fas fa-globe',
                 'description' => 'Globe',
             ],
        'fa-wrench' =>
             [
                 'fa-icon' => 'fas fa-wrench',
                 'description' => 'Wrench',
             ],
        'fa-tasks' =>
             [
                 'fa-icon' => 'fas fa-tasks',
                 'description' => 'Tasks',
             ],
        'fa-filter' =>
             [
                 'fa-icon' => 'fas fa-filter',
                 'description' => 'Filter',
             ],
        'fa-briefcase' =>
             [
                 'fa-icon' => 'fas fa-briefcase',
                 'description' => 'Briefcase',
             ],
        'fa-arrows-alt' =>
             [
                 'fa-icon' => 'fas fa-arrows-alt',
                 'description' => 'Arrows Alt',
             ],
        'fa-users' =>
             [
                 'fa-icon' => 'fas fa-users',
                 'description' => 'Users',
             ],
        'fa-link' =>
             [
                 'fa-icon' => 'fas fa-link',
                 'description' => 'Link',
             ],
        'fa-cloud' =>
             [
                 'fa-icon' => 'fas fa-cloud',
                 'description' => 'Cloud',
             ],
        'fa-flask' =>
             [
                 'fa-icon' => 'fas fa-flask',
                 'description' => 'Flask',
             ],
        'fa-scissors' =>
             [
                 'fa-icon' => 'fas fa-cut',
                 'description' => 'Scissors',
             ],
        'fa-paperclip' =>
             [
                 'fa-icon' => 'fas fa-paperclip',
                 'description' => 'Paperclip',
             ],
        'fa-floppy-o' =>
             [
                 'fa-icon' => 'far fa-save',
                 'description' => 'Floppy Outlined',
             ],
        'fa-square' =>
             [
                 'fa-icon' => 'fas fa-square',
                 'description' => 'Square',
             ],
        'fa-bars' =>
             [
                 'fa-icon' => 'fas fa-bars',
                 'description' => 'Bars',
             ],
        'fa-list-ul' =>
             [
                 'fa-icon' => 'fas fa-list-ul',
                 'description' => 'List-ul',
             ],
        'fa-strikethrough' =>
             [
                 'fa-icon' => 'fas fa-strikethrough',
                 'description' => 'Strikethrough',
             ],
        'fa-underline' =>
             [
                 'fa-icon' => 'fas fa-underline',
                 'description' => 'Underline',
             ],
        'fa-magic' =>
             [
                 'fa-icon' => 'fas fa-magic',
                 'description' => 'Magic',
             ],
        'fa-truck' =>
             [
                 'fa-icon' => 'fas fa-truck',
                 'description' => 'Truck',
             ],
        'fa-pinterest' =>
             [
                 'fa-icon' => 'fab fa-pinterest',
                 'description' => 'Pinterest',
             ],
        'fa-google-plus' =>
             [
                 'fa-icon' => 'fab fa-google-plus',
                 'description' => 'Google Plus',
             ],
        'fa-money' =>
             [
                 'fa-icon' => 'far fa-money-bill-alt',
                 'description' => 'Money',
             ],
        'fa-caret-down' =>
             [
                 'fa-icon' => 'fas fa-caret-down',
                 'description' => 'Caret Down',
             ],
        'fa-caret-up' =>
             [
                 'fa-icon' => 'fas fa-caret-up',
                 'description' => 'Caret Up',
             ],
        'fa-caret-left' =>
             [
                 'fa-icon' => 'fas fa-caret-left',
                 'description' => 'Caret Left',
             ],
        'fa-caret-right' =>
             [
                 'fa-icon' => 'fas fa-caret-right',
                 'description' => 'Caret Right',
             ],
        'fa-columns' =>
             [
                 'fa-icon' => 'fas fa-columns',
                 'description' => 'Columns',
             ],
        'fa-sort' =>
             [
                 'fa-icon' => 'fas fa-sort',
                 'description' => 'Sort',
             ],
        'fa-sort-desc' =>
             [
                 'fa-icon' => 'fas fa-sort-down',
                 'description' => 'Sort Descending',
             ],
        'fa-sort-asc' =>
             [
                 'fa-icon' => 'fas fa-sort-up',
                 'description' => 'Sort Ascending',
             ],
        'fa-envelope' =>
             [
                 'fa-icon' => 'fas fa-envelope',
                 'description' => 'Envelope',
             ],
        'fa-linkedin' =>
             [
                 'fa-icon' => 'fab fa-linkedin',
                 'description' => 'LinkedIn',
             ],
        'fa-undo' =>
             [
                 'fa-icon' => 'fas fa-undo',
                 'description' => 'Undo',
             ],
        'fa-comment-o' =>
             [
                 'fa-icon' => 'far fa-comment',
                 'description' => 'Comment-o',
             ],
        'fa-comments-o' =>
             [
                 'fa-icon' => 'far fa-comments',
                 'description' => 'Comments-o',
             ],
        'fa-bolt' =>
             [
                 'fa-icon' => 'fas fa-bolt',
                 'description' => 'Lightning Bolt',
             ],
        'fa-sitemap' =>
             [
                 'fa-icon' => 'fas fa-sitemap',
                 'description' => 'Sitemap',
             ],
        'fa-umbrella' =>
             [
                 'fa-icon' => 'fas fa-umbrella',
                 'description' => 'Umbrella',
             ],
        'fa-lightbulb-o' =>
             [
                 'fa-icon' => 'far fa-lightbulb',
                 'description' => 'Lightbulb Outlined',
             ],
        'fa-exchange' =>
             [
                 'fa-icon' => 'fas fa-exchange-alt',
                 'description' => 'Exchange',
             ],
        'fa-cloud-download' =>
             [
                 'fa-icon' => 'fas fa-cloud-download-alt',
                 'description' => 'Cloud Download',
             ],
        'fa-cloud-upload' =>
             [
                 'fa-icon' => 'fas fa-cloud-upload-alt',
                 'description' => 'Cloud Upload',
             ],
        'fa-user-md' =>
             [
                 'fa-icon' => 'fas fa-user-md',
                 'description' => 'User-md',
             ],
        'fa-stethoscope' =>
             [
                 'fa-icon' => 'fas fa-stethoscope',
                 'description' => 'Stethoscope',
             ],
        'fa-suitcase' =>
             [
                 'fa-icon' => 'fas fa-suitcase',
                 'description' => 'Suitcase',
             ],
        'fa-coffee' =>
             [
                 'fa-icon' => 'fas fa-coffee',
                 'description' => 'Coffee',
             ],
        'fa-cutlery' =>
             [
                 'fa-icon' => 'fas fa-utensils',
                 'description' => 'Cutlery',
             ],
        'fa-building-o' =>
             [
                 'fa-icon' => 'far fa-building',
                 'description' => 'Building Outlined',
             ],
        'fa-hospital-o' =>
             [
                 'fa-icon' => 'far fa-hospital',
                 'description' => 'Hospital Outlined',
             ],
        'fa-ambulance' =>
             [
                 'fa-icon' => 'fas fa-ambulance',
                 'description' => 'Ambulance',
             ],
        'fa-medkit' =>
             [
                 'fa-icon' => 'fas fa-medkit',
                 'description' => 'Medkit',
             ],
        'fab fa-figma' =>
             [
                 'fa-icon' => 'fab fa-figma',
                 'description' => 'Figma Logo',
             ],
        'fa-fighter-jet' =>
             [
                 'fa-icon' => 'fas fa-fighter-jet',
                 'description' => 'Fighter-jet',
             ],
        'fa-beer' =>
             [
                 'fa-icon' => 'fas fa-beer',
                 'description' => 'Beer',
             ],
        'fa-h-square' =>
             [
                 'fa-icon' => 'fas fa-h-square',
                 'description' => 'H Square',
             ],
        'fa-plus-square' =>
             [
                 'fa-icon' => 'fas fa-plus-square',
                 'description' => 'Plus Square',
             ],
        'fa-angle-double-left' =>
             [
                 'fa-icon' => 'fas fa-angle-double-left',
                 'description' => 'Angle Double Left',
             ],
        'fa-angle-double-right' =>
             [
                 'fa-icon' => 'fas fa-angle-double-right',
                 'description' => 'Angle Double Right',
             ],
        'fa-angle-double-up' =>
             [
                 'fa-icon' => 'fas fa-angle-double-up',
                 'description' => 'Angle Double Up',
             ],
        'fa-angle-double-down' =>
             [
                 'fa-icon' => 'fas fa-angle-double-down',
                 'description' => 'Angle Double Down',
             ],
        'fa-angle-left' =>
             [
                 'fa-icon' => 'fas fa-angle-left',
                 'description' => 'Angle-left',
             ],
        'fa-angle-right' =>
             [
                 'fa-icon' => 'fas fa-angle-right',
                 'description' => 'Angle-right',
             ],
        'fa-angle-up' =>
             [
                 'fa-icon' => 'fas fa-angle-up',
                 'description' => 'Angle-up',
             ],
        'fa-angle-down' =>
             [
                 'fa-icon' => 'fas fa-angle-down',
                 'description' => 'Angle-down',
             ],
        'fa-desktop' =>
             [
                 'fa-icon' => 'fas fa-desktop',
                 'description' => 'Desktop',
             ],
        'fa-laptop' =>
             [
                 'fa-icon' => 'fas fa-laptop',
                 'description' => 'Laptop',
             ],
        'fa-tablet' =>
             [
                 'fa-icon' => 'fas fa-tablet',
                 'description' => 'Tablet',
             ],
        'fa-mobile' =>
             [
                 'fa-icon' => 'fas fa-mobile',
                 'description' => 'Mobile Phone',
             ],
        'fa-quote-left' =>
             [
                 'fa-icon' => 'fas fa-quote-left',
                 'description' => 'Quote-left',
             ],
        'fa-quote-right' =>
             [
                 'fa-icon' => 'fas fa-quote-right',
                 'description' => 'Quote-right',
             ],
        'fa-spinner' =>
             [
                 'fa-icon' => 'fas fa-spinner',
                 'description' => 'Spinner',
             ],
        'fa-reply' =>
             [
                 'fa-icon' => 'fas fa-reply',
                 'description' => 'Reply',
             ],
        'fa-github-alt' =>
             [
                 'fa-icon' => 'fab fa-github-alt',
                 'description' => 'GitHub Alt',
             ],
        'fa-folder-open-o' =>
             [
                 'fa-icon' => 'far fa-folder-open',
                 'description' => 'Folder Open Outlined',
             ],
        'fa-smile-o' =>
             [
                 'fa-icon' => 'far fa-smile',
                 'description' => 'Smile Outlined',
             ],
        'fa-frown-o' =>
             [
                 'fa-icon' => 'far fa-frown',
                 'description' => 'Frown Outlined',
             ],
        'fa-meh-o' =>
             [
                 'fa-icon' => 'far fa-meh',
                 'description' => 'Meh Outlined',
             ],
        'fa-gamepad' =>
             [
                 'fa-icon' => 'fas fa-gamepad',
                 'description' => 'Gamepad',
             ],
        'fa-keyboard-o' =>
             [
                 'fa-icon' => 'far fa-keyboard',
                 'description' => 'Keyboard Outlined',
             ],
        'fa-terminal' =>
             [
                 'fa-icon' => 'fas fa-terminal',
                 'description' => 'Terminal',
             ],
        'fa-code' =>
             [
                 'fa-icon' => 'fas fa-code',
                 'description' => 'Code',
             ],
        'fa-reply-all' =>
             [
                 'fa-icon' => 'fas fa-reply-all',
                 'description' => 'Reply-all',
             ],
        'fa-star-half-o' =>
             [
                 'fa-icon' => 'far fa-star-half',
                 'description' => 'Star Half Outlined',
             ],
        'fa-location-arrow' =>
             [
                 'fa-icon' => 'fas fa-location-arrow',
                 'description' => 'Location-arrow',
             ],
        'fa-crop' =>
             [
                 'fa-icon' => 'fas fa-crop',
                 'description' => 'Crop',
             ],
        'fa-chain-broken' =>
             [
                 'fa-icon' => 'fas fa-unlink',
                 'description' => 'Chain Broken',
             ],
        'fa-question' =>
             [
                 'fa-icon' => 'fas fa-question',
                 'description' => 'Question',
             ],
        'fa-info' =>
             [
                 'fa-icon' => 'fas fa-info',
                 'description' => 'Info',
             ],
        'fa-exclamation' =>
             [
                 'fa-icon' => 'fas fa-exclamation',
                 'description' => 'Exclamation',
             ],
        'fa-superscript' =>
             [
                 'fa-icon' => 'fas fa-superscript',
                 'description' => 'Superscript',
             ],
        'fa-subscript' =>
             [
                 'fa-icon' => 'fas fa-subscript',
                 'description' => 'Subscript',
             ],
        'fa-eraser' =>
             [
                 'fa-icon' => 'fas fa-eraser',
                 'description' => 'Eraser',
             ],
        'fa-puzzle-piece' =>
             [
                 'fa-icon' => 'fas fa-puzzle-piece',
                 'description' => 'Puzzle Piece',
             ],
        'fa-microphone' =>
             [
                 'fa-icon' => 'fas fa-microphone',
                 'description' => 'Microphone',
             ],
        'fa-microphone-slash' =>
             [
                 'fa-icon' => 'fas fa-microphone-slash',
                 'description' => 'Microphone Slash',
             ],
        'fa-calendar-o' =>
             [
                 'fa-icon' => 'far fa-calendar',
                 'description' => 'Calendar-o',
             ],
        'fa-fire-extinguisher' =>
             [
                 'fa-icon' => 'fas fa-fire-extinguisher',
                 'description' => 'Fire-extinguisher',
             ],
        'fa-rocket' =>
             [
                 'fa-icon' => 'fas fa-rocket',
                 'description' => 'Rocket',
             ],
        'fa-maxcdn' =>
             [
                 'fa-icon' => 'fab fa-maxcdn',
                 'description' => 'MaxCDN',
             ],
        'fa-chevron-circle-left' =>
             [
                 'fa-icon' => 'fas fa-chevron-circle-left',
                 'description' => 'Chevron Circle Left',
             ],
        'fa-chevron-circle-right' =>
             [
                 'fa-icon' => 'fas fa-chevron-circle-right',
                 'description' => 'Chevron Circle Right',
             ],
        'fa-chevron-circle-up' =>
             [
                 'fa-icon' => 'fas fa-chevron-circle-up',
                 'description' => 'Chevron Circle Up',
             ],
        'fa-chevron-circle-down' =>
             [
                 'fa-icon' => 'fas fa-chevron-circle-down',
                 'description' => 'Chevron Circle Down',
             ],
        'fa-html5' =>
             [
                 'fa-icon' => 'fab fa-html5',
                 'description' => 'HTML 5 Logo',
             ],
        'fa-css3' =>
             [
                 'fa-icon' => 'fab fa-css3',
                 'description' => 'CSS 3 Logo',
             ],
        'fa-anchor' =>
             [
                 'fa-icon' => 'fas fa-anchor',
                 'description' => 'Anchor',
             ],
        'fa-ellipsis-h' =>
             [
                 'fa-icon' => 'fas fa-ellipsis-h',
                 'description' => 'Ellipsis Horizontal',
             ],
        'fa-ellipsis-v' =>
             [
                 'fa-icon' => 'fas fa-ellipsis-v',
                 'description' => 'Ellipsis Vertical',
             ],
        'fa-play-circle' =>
             [
                 'fa-icon' => 'fas fa-play-circle',
                 'description' => 'Play Circle',
             ],
        'fa-ticket' =>
             [
                 'fa-icon' => 'fas fa-ticket-alt',
                 'description' => 'Ticket',
             ],
        'fa-minus-square' =>
             [
                 'fa-icon' => 'fas fa-minus-square',
                 'description' => 'Minus Square',
             ],
        'fa-minus-square-o' =>
             [
                 'fa-icon' => 'far fa-minus-square',
                 'description' => 'Minus Square Outlined',
             ],
        'fa-level-up' =>
             [
                 'fa-icon' => 'fas fa-level-up-alt',
                 'description' => 'Level Up',
             ],
        'fa-level-down' =>
             [
                 'fa-icon' => 'fas fa-level-down-alt',
                 'description' => 'Level Down',
             ],
        'fa-check-square' =>
             [
                 'fa-icon' => 'fas fa-check-square',
                 'description' => 'Check Square',
             ],
        'fa-pencil-square' =>
             [
                 'fa-icon' => 'fas fa-pen-square',
                 'description' => 'Pencil Square',
             ],
        'fa-external-link-square' =>
             [
                 'fa-icon' => 'fas fa-external-link-square-alt',
                 'description' => 'External Link Square',
             ],
        'fa-share-square' =>
             [
                 'fa-icon' => 'fas fa-share-square',
                 'description' => 'Share Square',
             ],
        'fa-compass' =>
             [
                 'fa-icon' => 'fas fa-compass',
                 'description' => 'Compass',
             ],
        'fa-caret-square-o-down' =>
             [
                 'fa-icon' => 'far fa-caret-square-down',
                 'description' => 'Caret Square Outlined Down',
             ],
        'fa-caret-square-o-up' =>
             [
                 'fa-icon' => 'far fa-caret-square-up',
                 'description' => 'Caret Square Outlined Up',
             ],
        'fa-caret-square-o-right' =>
             [
                 'fa-icon' => 'far fa-caret-square-right',
                 'description' => 'Caret Square Outlined Right',
             ],
        'fa-eur' =>
             [
                 'fa-icon' => 'fas fa-euro-sign',
                 'description' => 'Euro (EUR)',
             ],
        'fa-gbp' =>
             [
                 'fa-icon' => 'fas fa-pound-sign',
                 'description' => 'GBP',
             ],
        'fa-usd' =>
             [
                 'fa-icon' => 'fas fa-dollar-sign',
                 'description' => 'US Dollar',
             ],
        'fa-inr' =>
             [
                 'fa-icon' => 'fas fa-rupee-sign',
                 'description' => 'Indian Rupee (INR)',
             ],
        'fa-jpy' =>
             [
                 'fa-icon' => 'fas fa-yen-sign',
                 'description' => 'Japanese Yen (JPY)',
             ],
        'fa-rub' =>
             [
                 'fa-icon' => 'fas fa-ruble-sign',
                 'description' => 'Russian Ruble (RUB)',
             ],
        'fa-krw' =>
             [
                 'fa-icon' => 'fas fa-won-sign',
                 'description' => 'Korean Won (KRW)',
             ],
        'fa-btc' =>
             [
                 'fa-icon' => 'fab fa-btc',
                 'description' => 'Bitcoin (BTC)',
             ],
        'fa-sort-alpha-asc' =>
             [
                 'fa-icon' => 'fas fa-sort-alpha-down',
                 'description' => 'Sort Alpha Ascending',
             ],
        'fa-sort-alpha-desc' =>
             [
                 'fa-icon' => 'fas fa-sort-alpha-up',
                 'description' => 'Sort Alpha Descending',
             ],
        'fa-sort-amount-asc' =>
             [
                 'fa-icon' => 'fas fa-sort-amount-down',
                 'description' => 'Sort Amount Ascending',
             ],
        'fa-sort-amount-desc' =>
             [
                 'fa-icon' => 'fas fa-sort-amount-up',
                 'description' => 'Sort Amount Descending',
             ],
        'fa-sort-numeric-asc' =>
             [
                 'fa-icon' => 'fas fa-sort-numeric-down',
                 'description' => 'Sort Numeric Ascending',
             ],
        'fa-sort-numeric-desc' =>
             [
                 'fa-icon' => 'fas fa-sort-numeric-up',
                 'description' => 'Sort Numeric Descending',
             ],
        'fa-thumbs-up' =>
             [
                 'fa-icon' => 'fas fa-thumbs-up',
                 'description' => 'Thumbs-up',
             ],
        'fa-thumbs-down' =>
             [
                 'fa-icon' => 'fas fa-thumbs-down',
                 'description' => 'Thumbs-down',
             ],
        'fa-youtube' =>
             [
                 'fa-icon' => 'fab fa-youtube',
                 'description' => 'YouTube',
             ],
        'fa-xing' =>
             [
                 'fa-icon' => 'fab fa-xing',
                 'description' => 'Xing',
             ],
        'fa-youtube-play' =>
             [
                 'fa-icon' => 'fab fa-youtube',
                 'description' => 'YouTube Play',
             ],
        'fa-dropbox' =>
             [
                 'fa-icon' => 'fab fa-dropbox',
                 'description' => 'Dropbox',
             ],
        'fa-stack-overflow' =>
             [
                 'fa-icon' => 'fab fa-stack-overflow',
                 'description' => 'Stack Overflow',
             ],
        'fa-instagram' =>
             [
                 'fa-icon' => 'fab fa-instagram',
                 'description' => 'Instagram',
             ],
        'fa-flickr' =>
             [
                 'fa-icon' => 'fab fa-flickr',
                 'description' => 'Flickr',
             ],
        'fa-adn' =>
             [
                 'fa-icon' => 'fab fa-adn',
                 'description' => 'App.net',
             ],
        'fa-bitbucket' =>
             [
                 'fa-icon' => 'fab fa-bitbucket',
                 'description' => 'Bitbucket',
             ],
        'fa-bitbucket-square' =>
             [
                 'fa-icon' => 'fab fa-bitbucket',
                 'description' => 'Bitbucket Square',
             ],
        'fa-tumblr' =>
             [
                 'fa-icon' => 'fab fa-tumblr',
                 'description' => 'Tumblr',
             ],
        'fa-tumblr-square' =>
             [
                 'fa-icon' => 'fab fa-tumblr-square',
                 'description' => 'Tumblr Square',
             ],
        'fa-long-arrow-down' =>
             [
                 'fa-icon' => 'fas fa-long-arrow-alt-down',
                 'description' => 'Long Arrow Down',
             ],
        'fa-long-arrow-up' =>
             [
                 'fa-icon' => 'fas fa-long-arrow-alt-up',
                 'description' => 'Long Arrow Up',
             ],
        'fa-long-arrow-left' =>
             [
                 'fa-icon' => 'fas fa-long-arrow-alt-left',
                 'description' => 'Long Arrow Left',
             ],
        'fa-long-arrow-right' =>
             [
                 'fa-icon' => 'fas fa-long-arrow-alt-right',
                 'description' => 'Long Arrow Right',
             ],
        'fa-apple' =>
             [
                 'fa-icon' => 'fab fa-apple',
                 'description' => 'Apple',
             ],
        'fa-windows' =>
             [
                 'fa-icon' => 'fab fa-windows',
                 'description' => 'Windows',
             ],
        'fa-android' =>
             [
                 'fa-icon' => 'fab fa-android',
                 'description' => 'Android',
             ],
        'fa-linux' =>
             [
                 'fa-icon' => 'fab fa-linux',
                 'description' => 'Linux',
             ],
        'fa-dribbble' =>
             [
                 'fa-icon' => 'fab fa-dribbble',
                 'description' => 'Dribbble',
             ],
        'fa-skype' =>
             [
                 'fa-icon' => 'fab fa-skype',
                 'description' => 'Skype',
             ],
        'fa-foursquare' =>
             [
                 'fa-icon' => 'fab fa-foursquare',
                 'description' => 'Foursquare',
             ],
        'fa-trello' =>
             [
                 'fa-icon' => 'fab fa-trello',
                 'description' => 'Trello',
             ],
        'fa-female' =>
             [
                 'fa-icon' => 'fas fa-female',
                 'description' => 'Female',
             ],
        'fa-male' =>
             [
                 'fa-icon' => 'fas fa-male',
                 'description' => 'Male',
             ],
        'fa-gratipay' =>
             [
                 'fa-icon' => 'fab fa-gratipay',
                 'description' => 'Gratipay (Gittip)',
             ],
        'fa-sun-o' =>
             [
                 'fa-icon' => 'far fa-sun',
                 'description' => 'Sun Outlined',
             ],
        'fa-moon-o' =>
             [
                 'fa-icon' => 'far fa-moon',
                 'description' => 'Moon Outlined',
             ],
        'fa-bug' =>
             [
                 'fa-icon' => 'fas fa-bug',
                 'description' => 'Bug',
             ],
        'fa-vk' =>
             [
                 'fa-icon' => 'fab fa-vk',
                 'description' => 'VK',
             ],
        'fa-weibo' =>
             [
                 'fa-icon' => 'fab fa-weibo',
                 'description' => 'Weibo',
             ],
        'fa-renren' =>
             [
                 'fa-icon' => 'fab fa-renren',
                 'description' => 'Renren',
             ],
        'fa-pagelines' =>
             [
                 'fa-icon' => 'fab fa-pagelines',
                 'description' => 'Pagelines',
             ],
        'fa-stack-exchange' =>
             [
                 'fa-icon' => 'fab fa-stack-exchange',
                 'description' => 'Stack Exchange',
             ],
        'fa-arrow-circle-o-right' =>
             [
                 'fa-icon' => 'fas fa-arrow-alt-circle-right',
                 'description' => 'Arrow Circle Outlined Right',
             ],
        'fa-arrow-circle-o-left' =>
             [
                 'fa-icon' => 'fas fa-arrow-alt-circle-left',
                 'description' => 'Arrow Circle Outlined Left',
             ],
        'fa-caret-square-o-left' =>
             [
                 'fa-icon' => 'far fa-caret-square-left',
                 'description' => 'Caret Square Outlined Left',
             ],
        'fa-wheelchair' =>
             [
                 'fa-icon' => 'fas fa-wheelchair',
                 'description' => 'Wheelchair',
             ],
        'fa-vimeo-square' =>
             [
                 'fa-icon' => 'fab fa-vimeo-square',
                 'description' => 'Vimeo Square',
             ],
        'fa-try' =>
             [
                 'fa-icon' => 'fas fa-lira-sign',
                 'description' => 'Turkish Lira (TRY)',
             ],
        'fa-plus-square-o' =>
             [
                 'fa-icon' => 'far fa-plus-square',
                 'description' => 'Plus Square Outlined',
             ],
        'fa-space-shuttle' =>
             [
                 'fa-icon' => 'fas fa-space-shuttle',
                 'description' => 'Space Shuttle',
             ],
        'fa-slack' =>
             [
                 'fa-icon' => 'fab fa-slack',
                 'description' => 'Slack Logo',
             ],
        'fa-envelope-square' =>
             [
                 'fa-icon' => 'fas fa-envelope-square',
                 'description' => 'Envelope Square',
             ],
        'fa-wordpress' =>
             [
                 'fa-icon' => 'fab fa-wordpress',
                 'description' => 'WordPress Logo',
             ],
        'fa-openid' =>
             [
                 'fa-icon' => 'fab fa-openid',
                 'description' => 'OpenID',
             ],
        'fa-university' =>
             [
                 'fa-icon' => 'fas fa-university',
                 'description' => 'University',
             ],
        'fa-graduation-cap' =>
             [
                 'fa-icon' => 'fas fa-graduation-cap',
                 'description' => 'Graduation Cap',
             ],
        'fa-yahoo' =>
             [
                 'fa-icon' => 'fab fa-yahoo',
                 'description' => 'Yahoo Logo',
             ],
        'fa-google' =>
             [
                 'fa-icon' => 'fab fa-google',
                 'description' => 'Google Logo',
             ],
        'fa-reddit' =>
             [
                 'fa-icon' => 'fab fa-reddit',
                 'description' => 'Reddit Logo',
             ],
        'fa-reddit-square' =>
             [
                 'fa-icon' => 'fab fa-reddit-square',
                 'description' => 'Reddit Square',
             ],
        'fa-stumbleupon-circle' =>
             [
                 'fa-icon' => 'fab fa-stumbleupon-circle',
                 'description' => 'StumbleUpon Circle',
             ],
        'fa-stumbleupon' =>
             [
                 'fa-icon' => 'fab fa-stumbleupon',
                 'description' => 'StumbleUpon Logo',
             ],
        'fa-delicious' =>
             [
                 'fa-icon' => 'fab fa-delicious',
                 'description' => 'Delicious Logo',
             ],
        'fa-digg' =>
             [
                 'fa-icon' => 'fab fa-digg',
                 'description' => 'Digg Logo',
             ],
        'fa-pied-piper-pp' =>
             [
                 'fa-icon' => 'fab fa-pied-piper-pp',
                 'description' => 'Pied Piper PP Logo (Old)',
             ],
        'fa-pied-piper-alt' =>
             [
                 'fa-icon' => 'fab fa-pied-piper-alt',
                 'description' => 'Pied Piper Alternate Logo',
             ],
        'fa-drupal' =>
             [
                 'fa-icon' => 'fab fa-drupal',
                 'description' => 'Drupal Logo',
             ],
        'fa-joomla' =>
             [
                 'fa-icon' => 'fab fa-joomla',
                 'description' => 'Joomla Logo',
             ],
        'fa-language' =>
             [
                 'fa-icon' => 'fas fa-language',
                 'description' => 'Language',
             ],
        'fa-fax' =>
             [
                 'fa-icon' => 'fas fa-fax',
                 'description' => 'Fax',
             ],
        'fa-building' =>
             [
                 'fa-icon' => 'fas fa-building',
                 'description' => 'Building',
             ],
        'fa-child' =>
             [
                 'fa-icon' => 'fas fa-child',
                 'description' => 'Child',
             ],
        'fa-paw' =>
             [
                 'fa-icon' => 'fas fa-paw',
                 'description' => 'Paw',
             ],
        'fa-spoon' =>
             [
                 'fa-icon' => 'fas fa-utensil-spoon',
                 'description' => 'Spoon',
             ],
        'fa-cube' =>
             [
                 'fa-icon' => 'fas fa-cube',
                 'description' => 'Cube',
             ],
        'fa-cubes' =>
             [
                 'fa-icon' => 'fas fa-cubes',
                 'description' => 'Cubes',
             ],
        'fa-behance' =>
             [
                 'fa-icon' => 'fab fa-behance',
                 'description' => 'Behance',
             ],
        'fa-behance-square' =>
             [
                 'fa-icon' => 'fab fa-behance-square',
                 'description' => 'Behance Square',
             ],
        'fa-steam' =>
             [
                 'fa-icon' => 'fab fa-steam',
                 'description' => 'Steam',
             ],
        'fa-steam-square' =>
             [
                 'fa-icon' => 'fab fa-steam-square',
                 'description' => 'Steam Square',
             ],
        'fa-recycle' =>
             [
                 'fa-icon' => 'fas fa-recycle',
                 'description' => 'Recycle',
             ],
        'fa-car' =>
             [
                 'fa-icon' => 'fas fa-car',
                 'description' => 'Car',
             ],
        'fa-taxi' =>
             [
                 'fa-icon' => 'fas fa-taxi',
                 'description' => 'Taxi',
             ],
        'fa-tree' =>
             [
                 'fa-icon' => 'fas fa-tree',
                 'description' => 'Tree',
             ],
        'fa-spotify' =>
             [
                 'fa-icon' => 'fab fa-spotify',
                 'description' => 'Spotify',
             ],
        'fa-deviantart' =>
             [
                 'fa-icon' => 'fab fa-deviantart',
                 'description' => 'DeviantART',
             ],
        'fa-soundcloud' =>
             [
                 'fa-icon' => 'fab fa-soundcloud',
                 'description' => 'SoundCloud',
             ],
        'fa-database' =>
             [
                 'fa-icon' => 'fas fa-database',
                 'description' => 'Database',
             ],
        'fa-vine' =>
             [
                 'fa-icon' => 'fab fa-vine',
                 'description' => 'Vine',
             ],
        'fa-codepen' =>
             [
                 'fa-icon' => 'fab fa-codepen',
                 'description' => 'Codepen',
             ],
        'fa-jsfiddle' =>
             [
                 'fa-icon' => 'fab fa-jsfiddle',
                 'description' => 'JsFiddle',
             ],
        'fa-life-ring' =>
             [
                 'fa-icon' => 'fas fa-life-ring',
                 'description' => 'Life Ring',
             ],
        'fa-circle-o-notch' =>
             [
                 'fa-icon' => 'fas fa-circle-notch',
                 'description' => 'Circle Outlined Notched',
             ],
        'fa-rebel' =>
             [
                 'fa-icon' => 'fab fa-rebel',
                 'description' => 'Rebel Alliance',
             ],
        'fa-empire' =>
             [
                 'fa-icon' => 'fab fa-empire',
                 'description' => 'Galactic Empire',
             ],
        'fa-hacker-news' =>
             [
                 'fa-icon' => 'fab fa-hacker-news',
                 'description' => 'Hacker News',
             ],
        'fa-tencent-weibo' =>
             [
                 'fa-icon' => 'fab fa-tencent-weibo',
                 'description' => 'Tencent Weibo',
             ],
        'fa-qq' =>
             [
                 'fa-icon' => 'fab fa-qq',
                 'description' => 'QQ',
             ],
        'fa-weixin' =>
             [
                 'fa-icon' => 'fab fa-weixin',
                 'description' => 'Weixin (WeChat)',
             ],
        'fa-paper-plane' =>
             [
                 'fa-icon' => 'fas fa-paper-plane',
                 'description' => 'Paper Plane',
             ],
        'fa-paper-plane-o' =>
             [
                 'fa-icon' => 'far fa-paper-plane',
                 'description' => 'Paper Plane Outlined',
             ],
        'fa-circle-thin' =>
             [
                 'fa-icon' => 'far fa-circle',
                 'description' => 'Circle Outlined Thin',
             ],
        'fa-header' =>
             [
                 'fa-icon' => 'fas fa-heading',
                 'description' => 'Header',
             ],
        'fa-paragraph' =>
             [
                 'fa-icon' => 'fas fa-paragraph',
                 'description' => 'Paragraph',
             ],
        'fa-sliders' =>
             [
                 'fa-icon' => 'fas fa-sliders-h',
                 'description' => 'Sliders',
             ],
        'fa-bomb' =>
             [
                 'fa-icon' => 'fas fa-bomb',
                 'description' => 'Bomb',
             ],
        'fa-futbol-o' =>
             [
                 'fa-icon' => 'far fa-futbol',
                 'description' => 'Futbol Outlined',
             ],
        'fa-tty' =>
             [
                 'fa-icon' => 'fas fa-tty',
                 'description' => 'TTY',
             ],
        'fa-binoculars' =>
             [
                 'fa-icon' => 'fas fa-binoculars',
                 'description' => 'Binoculars',
             ],
        'fa-plug' =>
             [
                 'fa-icon' => 'fas fa-plug',
                 'description' => 'Plug',
             ],
        'fa-slideshare' =>
             [
                 'fa-icon' => 'fab fa-slideshare',
                 'description' => 'Slideshare',
             ],
        'fa-twitch' =>
             [
                 'fa-icon' => 'fab fa-twitch',
                 'description' => 'Twitch',
             ],
        'fa-yelp' =>
             [
                 'fa-icon' => 'fab fa-yelp',
                 'description' => 'Yelp',
             ],
        'fa-newspaper-o' =>
             [
                 'fa-icon' => 'far fa-newspaper',
                 'description' => 'Newspaper Outlined',
             ],
        'fa-wifi' =>
             [
                 'fa-icon' => 'fas fa-wifi',
                 'description' => 'WiFi',
             ],
        'fa-calculator' =>
             [
                 'fa-icon' => 'fas fa-calculator',
                 'description' => 'Calculator',
             ],
        'fa-paypal' =>
             [
                 'fa-icon' => 'fab fa-paypal',
                 'description' => 'Paypal',
             ],
        'fa-google-wallet' =>
             [
                 'fa-icon' => 'fab fa-google-wallet',
                 'description' => 'Google Wallet',
             ],
        'fa-cc-visa' =>
             [
                 'fa-icon' => 'fab fa-cc-visa',
                 'description' => 'Visa Credit Card',
             ],
        'fa-cc-mastercard' =>
             [
                 'fa-icon' => 'fab fa-cc-mastercard',
                 'description' => 'MasterCard Credit Card',
             ],
        'fa-cc-discover' =>
             [
                 'fa-icon' => 'fab fa-cc-discover',
                 'description' => 'Discover Credit Card',
             ],
        'fa-cc-amex' =>
             [
                 'fa-icon' => 'fab fa-cc-amex',
                 'description' => 'American Express Credit Card',
             ],
        'fa-cc-paypal' =>
             [
                 'fa-icon' => 'fab fa-cc-paypal',
                 'description' => 'Paypal Credit Card',
             ],
        'fa-cc-stripe' =>
             [
                 'fa-icon' => 'fab fa-cc-stripe',
                 'description' => 'Stripe Credit Card',
             ],
        'fa-trash' =>
             [
                 'fa-icon' => 'fas fa-trash',
                 'description' => 'Trash',
             ],
        'fa-copyright' =>
             [
                 'fa-icon' => 'fas fa-copyright',
                 'description' => 'Copyright',
             ],
        'fa-at' =>
             [
                 'fa-icon' => 'fas fa-at',
                 'description' => 'At',
             ],
        'fa-eyedropper' =>
             [
                 'fa-icon' => 'fas fa-eye-dropper',
                 'description' => 'Eyedropper',
             ],
        'fa-paint-brush' =>
             [
                 'fa-icon' => 'fas fa-paint-brush',
                 'description' => 'Paint Brush',
             ],
        'fa-birthday-cake' =>
             [
                 'fa-icon' => 'fas fa-birthday-cake',
                 'description' => 'Birthday Cake',
             ],
        'fa-area-chart' =>
             [
                 'fa-icon' => 'fas fa-chart-area',
                 'description' => 'Area Chart',
             ],
        'fa-pie-chart' =>
             [
                 'fa-icon' => 'fas fa-chart-pie',
                 'description' => 'Pie Chart',
             ],
        'fa-line-chart' =>
             [
                 'fa-icon' => 'fas fa-chart-line',
                 'description' => 'Line Chart',
             ],
        'fa-lastfm' =>
             [
                 'fa-icon' => 'fab fa-lastfm',
                 'description' => 'Last.fm',
             ],
        'fa-lastfm-square' =>
             [
                 'fa-icon' => 'fab fa-lastfm-square',
                 'description' => 'Last.fm Square',
             ],
        'fa-toggle-off' =>
             [
                 'fa-icon' => 'fas fa-toggle-off',
                 'description' => 'Toggle Off',
             ],
        'fa-toggle-on' =>
             [
                 'fa-icon' => 'fas fa-toggle-on',
                 'description' => 'Toggle On',
             ],
        'fa-bicycle' =>
             [
                 'fa-icon' => 'fas fa-bicycle',
                 'description' => 'Bicycle',
             ],
        'fa-bus' =>
             [
                 'fa-icon' => 'fas fa-bus',
                 'description' => 'Bus',
             ],
        'fa-ioxhost' =>
             [
                 'fa-icon' => 'fab fa-ioxhost',
                 'description' => 'Ioxhost',
             ],
        'fa-angellist' =>
             [
                 'fa-icon' => 'fab fa-angellist',
                 'description' => 'AngelList',
             ],
        'fa-cc' =>
             [
                 'fa-icon' => 'far fa-closed-captioning',
                 'description' => 'Closed Captions',
             ],
        'fa-ils' =>
             [
                 'fa-icon' => 'fas fa-shekel-sign',
                 'description' => 'Shekel (ILS)',
             ],
        'fa-meanpath' =>
             [
                 'fa-icon' => 'fab fa-font-awesome',
                 'description' => 'Meanpath',
             ],
        'fa-buysellads' =>
             [
                 'fa-icon' => 'fab fa-buysellads',
                 'description' => 'BuySellAds',
             ],
        'fa-connectdevelop' =>
             [
                 'fa-icon' => 'fab fa-connectdevelop',
                 'description' => 'Connect Develop',
             ],
        'fa-dashcube' =>
             [
                 'fa-icon' => 'fab fa-dashcube',
                 'description' => 'DashCube',
             ],
        'fa-forumbee' =>
             [
                 'fa-icon' => 'fab fa-forumbee',
                 'description' => 'Forumbee',
             ],
        'fa-leanpub' =>
             [
                 'fa-icon' => 'fab fa-leanpub',
                 'description' => 'Leanpub',
             ],
        'fa-sellsy' =>
             [
                 'fa-icon' => 'fab fa-sellsy',
                 'description' => 'Sellsy',
             ],
        'fa-shirtsinbulk' =>
             [
                 'fa-icon' => 'fab fa-shirtsinbulk',
                 'description' => 'Shirts in Bulk',
             ],
        'fa-simplybuilt' =>
             [
                 'fa-icon' => 'fab fa-simplybuilt',
                 'description' => 'SimplyBuilt',
             ],
        'fa-skyatlas' =>
             [
                 'fa-icon' => 'fab fa-skyatlas',
                 'description' => 'Skyatlas',
             ],
        'fa-cart-plus' =>
             [
                 'fa-icon' => 'fas fa-cart-plus',
                 'description' => 'Add to Shopping Cart',
             ],
        'fa-cart-arrow-down' =>
             [
                 'fa-icon' => 'fas fa-cart-arrow-down',
                 'description' => 'Shopping Cart Arrow Down',
             ],
        'fa-diamond' =>
             [
                 'fa-icon' => 'far fa-gem',
                 'description' => 'Diamond',
             ],
        'fa-ship' =>
             [
                 'fa-icon' => 'fas fa-ship',
                 'description' => 'Ship',
             ],
        'fa-user-secret' =>
             [
                 'fa-icon' => 'fas fa-user-secret',
                 'description' => 'User Secret',
             ],
        'fa-motorcycle' =>
             [
                 'fa-icon' => 'fas fa-motorcycle',
                 'description' => 'Motorcycle',
             ],
        'fa-street-view' =>
             [
                 'fa-icon' => 'fas fa-street-view',
                 'description' => 'Street View',
             ],
        'fa-heartbeat' =>
             [
                 'fa-icon' => 'fas fa-heartbeat',
                 'description' => 'Heartbeat',
             ],
        'fa-venus' =>
             [
                 'fa-icon' => 'fas fa-venus',
                 'description' => 'Venus',
             ],
        'fa-mars' =>
             [
                 'fa-icon' => 'fas fa-mars',
                 'description' => 'Mars',
             ],
        'fa-mercury' =>
             [
                 'fa-icon' => 'fas fa-mercury',
                 'description' => 'Mercury',
             ],
        'fa-transgender' =>
             [
                 'fa-icon' => 'fas fa-transgender',
                 'description' => 'Transgender',
             ],
        'fa-transgender-alt' =>
             [
                 'fa-icon' => 'fas fa-transgender-alt',
                 'description' => 'Transgender Alt',
             ],
        'fa-venus-double' =>
             [
                 'fa-icon' => 'fas fa-venus-double',
                 'description' => 'Venus Double',
             ],
        'fa-mars-double' =>
             [
                 'fa-icon' => 'fas fa-mars-double',
                 'description' => 'Mars Double',
             ],
        'fa-venus-mars' =>
             [
                 'fa-icon' => 'fas fa-venus-mars',
                 'description' => 'Venus Mars',
             ],
        'fa-mars-stroke' =>
             [
                 'fa-icon' => 'fas fa-mars-stroke',
                 'description' => 'Mars Stroke',
             ],
        'fa-mars-stroke-v' =>
             [
                 'fa-icon' => 'fas fa-mars-stroke-v',
                 'description' => 'Mars Stroke Vertical',
             ],
        'fa-mars-stroke-h' =>
             [
                 'fa-icon' => 'fas fa-mars-stroke-h',
                 'description' => 'Mars Stroke Horizontal',
             ],
        'fa-neuter' =>
             [
                 'fa-icon' => 'fas fa-neuter',
                 'description' => 'Neuter',
             ],
        'fa-genderless' =>
             [
                 'fa-icon' => 'fas fa-genderless',
                 'description' => 'Genderless',
             ],
        'fa-facebook-official' =>
             [
                 'fa-icon' => 'fab fa-facebook',
                 'description' => 'Facebook Official',
             ],
        'fa-whatsapp' =>
             [
                 'fa-icon' => 'fab fa-whatsapp',
                 'description' => 'What\'s App',
             ],
        'fa-server' =>
             [
                 'fa-icon' => 'fas fa-server',
                 'description' => 'Server',
             ],
        'fa-bed' =>
             [
                 'fa-icon' => 'fas fa-bed',
                 'description' => 'Bed',
             ],
        'fa-viacoin' =>
             [
                 'fa-icon' => 'fab fa-viacoin',
                 'description' => 'Viacoin (VIA)',
             ],
        'fa-train' =>
             [
                 'fa-icon' => 'fas fa-train',
                 'description' => 'Train',
             ],
        'fa-subway' =>
             [
                 'fa-icon' => 'fas fa-subway',
                 'description' => 'Subway',
             ],
        'fa-medium' =>
             [
                 'fa-icon' => 'fab fa-medium',
                 'description' => 'Medium',
             ],
        'fa-y-combinator' =>
             [
                 'fa-icon' => 'fab fa-y-combinator',
                 'description' => 'Y Combinator',
             ],
        'fa-optin-monster' =>
             [
                 'fa-icon' => 'fab fa-optin-monster',
                 'description' => 'Optin Monster',
             ],
        'fa-opencart' =>
             [
                 'fa-icon' => 'fab fa-opencart',
                 'description' => 'OpenCart',
             ],
        'fa-expeditedssl' =>
             [
                 'fa-icon' => 'fab fa-expeditedssl',
                 'description' => 'ExpeditedSSL',
             ],
        'fa-battery-full' =>
             [
                 'fa-icon' => 'fas fa-battery-full',
                 'description' => 'Battery Full',
             ],
        'fa-battery-three-quarters' =>
             [
                 'fa-icon' => 'fas fa-battery-three-quarters',
                 'description' => 'Battery 3/4 Full',
             ],
        'fa-battery-half' =>
             [
                 'fa-icon' => 'fas fa-battery-half',
                 'description' => 'Battery 1/2 Full',
             ],
        'fa-battery-quarter' =>
             [
                 'fa-icon' => 'fas fa-battery-quarter',
                 'description' => 'Battery 1/4 Full',
             ],
        'fa-battery-empty' =>
             [
                 'fa-icon' => 'fas fa-battery-empty',
                 'description' => 'Battery Empty',
             ],
        'fa-mouse-pointer' =>
             [
                 'fa-icon' => 'fas fa-mouse-pointer',
                 'description' => 'Mouse Pointer',
             ],
        'fa-i-cursor' =>
             [
                 'fa-icon' => 'fas fa-i-cursor',
                 'description' => 'I Beam Cursor',
             ],
        'fa-object-group' =>
             [
                 'fa-icon' => 'fas fa-object-group',
                 'description' => 'Object Group',
             ],
        'fa-object-ungroup' =>
             [
                 'fa-icon' => 'fas fa-object-ungroup',
                 'description' => 'Object Ungroup',
             ],
        'fa-cc-jcb' =>
             [
                 'fa-icon' => 'fab fa-cc-jcb',
                 'description' => 'JCB Credit Card',
             ],
        'fa-cc-diners-club' =>
             [
                 'fa-icon' => 'fab fa-cc-diners-club',
                 'description' => 'Diner\'s Club Credit Card',
             ],
        'fa-clone' =>
             [
                 'fa-icon' => 'fas fa-clone',
                 'description' => 'Clone',
             ],
        'fa-balance-scale' =>
             [
                 'fa-icon' => 'fas fa-balance-scale',
                 'description' => 'Balance Scale',
             ],
        'fa-hourglass-o' =>
             [
                 'fa-icon' => 'far fa-hourglass',
                 'description' => 'Hourglass Outlined',
             ],
        'fa-hourglass-start' =>
             [
                 'fa-icon' => 'fas fa-hourglass-start',
                 'description' => 'Hourglass Start',
             ],
        'fa-hourglass-half' =>
             [
                 'fa-icon' => 'fas fa-hourglass-half',
                 'description' => 'Hourglass Half',
             ],
        'fa-hourglass-end' =>
             [
                 'fa-icon' => 'fas fa-hourglass-end',
                 'description' => 'Hourglass End',
             ],
        'fa-hourglass' =>
             [
                 'fa-icon' => 'fas fa-hourglass',
                 'description' => 'Hourglass',
             ],
        'fa-hand-rock-o' =>
             [
                 'fa-icon' => 'far fa-hand-rock',
                 'description' => 'Rock (Hand)',
             ],
        'fa-hand-paper-o' =>
             [
                 'fa-icon' => 'far fa-hand-paper',
                 'description' => 'Paper (Hand)',
             ],
        'fa-hand-scissors-o' =>
             [
                 'fa-icon' => 'far fa-hand-scissors',
                 'description' => 'Scissors (Hand)',
             ],
        'fa-hand-lizard-o' =>
             [
                 'fa-icon' => 'far fa-hand-lizard',
                 'description' => 'Lizard (Hand)',
             ],
        'fa-hand-spock-o' =>
             [
                 'fa-icon' => 'far fa-hand-spock',
                 'description' => 'Spock (Hand)',
             ],
        'fa-hand-pointer-o' =>
             [
                 'fa-icon' => 'far fa-hand-pointer',
                 'description' => 'Hand Pointer',
             ],
        'fa-hand-peace-o' =>
             [
                 'fa-icon' => 'far fa-hand-peace',
                 'description' => 'Hand Peace',
             ],
        'fa-trademark' =>
             [
                 'fa-icon' => 'fas fa-trademark',
                 'description' => 'Trademark',
             ],
        'fa-registered' =>
             [
                 'fa-icon' => 'fas fa-registered',
                 'description' => 'Registered Trademark',
             ],
        'fa-creative-commons' =>
             [
                 'fa-icon' => 'fab fa-creative-commons',
                 'description' => 'Creative Commons',
             ],
        'fa-gg' =>
             [
                 'fa-icon' => 'fab fa-gg',
                 'description' => 'GG Currency',
             ],
        'fa-gg-circle' =>
             [
                 'fa-icon' => 'fab fa-gg-circle',
                 'description' => 'GG Currency Circle',
             ],
        'fa-odnoklassniki' =>
             [
                 'fa-icon' => 'fab fa-odnoklassniki',
                 'description' => 'Odnoklassniki',
             ],
        'fa-odnoklassniki-square' =>
             [
                 'fa-icon' => 'fab fa-odnoklassniki-square',
                 'description' => 'Odnoklassniki Square',
             ],
        'fa-get-pocket' =>
             [
                 'fa-icon' => 'fab fa-get-pocket',
                 'description' => 'Get Pocket',
             ],
        'fa-safari' =>
             [
                 'fa-icon' => 'fab fa-safari',
                 'description' => 'Safari',
             ],
        'fa-chrome' =>
             [
                 'fa-icon' => 'fab fa-chrome',
                 'description' => 'Chrome',
             ],
        'fa-firefox' =>
             [
                 'fa-icon' => 'fab fa-firefox',
                 'description' => 'Firefox',
             ],
        'fa-opera' =>
             [
                 'fa-icon' => 'fab fa-opera',
                 'description' => 'Opera',
             ],
        'fa-internet-explorer' =>
             [
                 'fa-icon' => 'fab fa-internet-explorer',
                 'description' => 'Internet-explorer',
             ],
        'fa-television' =>
             [
                 'fa-icon' => 'fas fa-tv',
                 'description' => 'Television',
             ],
        'fa-contao' =>
             [
                 'fa-icon' => 'fab fa-contao',
                 'description' => 'Contao',
             ],
        'fa-500px' =>
             [
                 'fa-icon' => 'fab fa-500px',
                 'description' => '500px',
             ],
        'fa-amazon' =>
             [
                 'fa-icon' => 'fab fa-amazon',
                 'description' => 'Amazon',
             ],
        'fa-calendar-plus-o' =>
             [
                 'fa-icon' => 'far fa-calendar-plus',
                 'description' => 'Calendar Plus Outlined',
             ],
        'fa-calendar-minus-o' =>
             [
                 'fa-icon' => 'far fa-calendar-minus',
                 'description' => 'Calendar Minus Outlined',
             ],
        'fa-calendar-times-o' =>
             [
                 'fa-icon' => 'far fa-calendar-times',
                 'description' => 'Calendar Times Outlined',
             ],
        'fa-calendar-check-o' =>
             [
                 'fa-icon' => 'far fa-calendar-check',
                 'description' => 'Calendar Check Outlined',
             ],
        'fa-industry' =>
             [
                 'fa-icon' => 'fas fa-industry',
                 'description' => 'Industry',
             ],
        'fa-map-pin' =>
             [
                 'fa-icon' => 'fas fa-map-pin',
                 'description' => 'Map Pin',
             ],
        'fa-map-signs' =>
             [
                 'fa-icon' => 'fas fa-map-signs',
                 'description' => 'Map Signs',
             ],
        'fa-map-o' =>
             [
                 'fa-icon' => 'far fa-map',
                 'description' => 'Map Outlined',
             ],
        'fa-map' =>
             [
                 'fa-icon' => 'fas fa-map',
                 'description' => 'Map',
             ],
        'fa-commenting' =>
             [
                 'fa-icon' => 'fas fa-comment-dots',
                 'description' => 'Commenting',
             ],
        'fa-commenting-o' =>
             [
                 'fa-icon' => 'far fa-comment-dots',
                 'description' => 'Commenting Outlined',
             ],
        'fa-houzz' =>
             [
                 'fa-icon' => 'fab fa-houzz',
                 'description' => 'Houzz',
             ],
        'fa-vimeo' =>
             [
                 'fa-icon' => 'fab fa-vimeo',
                 'description' => 'Vimeo',
             ],
        'fa-black-tie' =>
             [
                 'fa-icon' => 'fab fa-black-tie',
                 'description' => 'Font Awesome Black Tie',
             ],
        'fa-fonticons' =>
             [
                 'fa-icon' => 'fab fa-fonticons',
                 'description' => 'Fonticons',
             ],
        'fa-reddit-alien' =>
             [
                 'fa-icon' => 'fab fa-reddit-alien',
                 'description' => 'Reddit Alien',
             ],
        'fa-edge' =>
             [
                 'fa-icon' => 'fab fa-edge',
                 'description' => 'Edge Browser',
             ],
        'fa-credit-card-alt' =>
             [
                 'fa-icon' => 'fas fa-credit-card',
                 'description' => 'Credit Card',
             ],
        'fa-codiepie' =>
             [
                 'fa-icon' => 'fab fa-codiepie',
                 'description' => 'Codie Pie',
             ],
        'fa-modx' =>
             [
                 'fa-icon' => 'fab fa-modx',
                 'description' => 'MODX',
             ],
        'fa-fort-awesome' =>
             [
                 'fa-icon' => 'fab fa-fort-awesome',
                 'description' => 'Fort Awesome',
             ],
        'fa-usb' =>
             [
                 'fa-icon' => 'fab fa-usb',
                 'description' => 'USB',
             ],
        'fa-product-hunt' =>
             [
                 'fa-icon' => 'fab fa-product-hunt',
                 'description' => 'Product Hunt',
             ],
        'fa-mixcloud' =>
             [
                 'fa-icon' => 'fab fa-mixcloud',
                 'description' => 'Mixcloud',
             ],
        'fa-scribd' =>
             [
                 'fa-icon' => 'fab fa-scribd',
                 'description' => 'Scribd',
             ],
        'fa-pause-circle' =>
             [
                 'fa-icon' => 'fas fa-pause-circle',
                 'description' => 'Pause Circle',
             ],
        'fa-pause-circle-o' =>
             [
                 'fa-icon' => 'far fa-pause-circle',
                 'description' => 'Pause Circle Outlined',
             ],
        'fa-stop-circle' =>
             [
                 'fa-icon' => 'fas fa-stop-circle',
                 'description' => 'Stop Circle',
             ],
        'fa-stop-circle-o' =>
             [
                 'fa-icon' => 'far fa-stop-circle',
                 'description' => 'Stop Circle Outlined',
             ],
        'fa-shopping-bag' =>
             [
                 'fa-icon' => 'fas fa-shopping-bag',
                 'description' => 'Shopping Bag',
             ],
        'fa-shopping-basket' =>
             [
                 'fa-icon' => 'fas fa-shopping-basket',
                 'description' => 'Shopping Basket',
             ],
        'fa-hashtag' =>
             [
                 'fa-icon' => 'fas fa-hashtag',
                 'description' => 'Hashtag',
             ],
        'fa-bluetooth' =>
             [
                 'fa-icon' => 'fab fa-bluetooth',
                 'description' => 'Bluetooth',
             ],
        'fa-bluetooth-b' =>
             [
                 'fa-icon' => 'fab fa-bluetooth-b',
                 'description' => 'Bluetooth',
             ],
        'fa-percent' =>
             [
                 'fa-icon' => 'fas fa-percent',
                 'description' => 'Percent',
             ],
        'fa-gitlab' =>
             [
                 'fa-icon' => 'fab fa-gitlab',
                 'description' => 'GitLab',
             ],
        'fa-wpbeginner' =>
             [
                 'fa-icon' => 'fab fa-wpbeginner',
                 'description' => 'WPBeginner',
             ],
        'fa-wpforms' =>
             [
                 'fa-icon' => 'fab fa-wpforms',
                 'description' => 'WPForms',
             ],
        'fa-envira' =>
             [
                 'fa-icon' => 'fab fa-envira',
                 'description' => 'Envira Gallery',
             ],
        'fa-universal-access' =>
             [
                 'fa-icon' => 'fas fa-universal-access',
                 'description' => 'Universal Access',
             ],
        'fa-wheelchair-alt' =>
             [
                 'fa-icon' => 'fab fa-accessible-icon',
                 'description' => 'Wheelchair Alt',
             ],
        'fa-question-circle-o' =>
             [
                 'fa-icon' => 'far fa-question-circle',
                 'description' => 'Question Circle Outlined',
             ],
        'fa-blind' =>
             [
                 'fa-icon' => 'fas fa-blind',
                 'description' => 'Blind',
             ],
        'fa-audio-description' =>
             [
                 'fa-icon' => 'fas fa-audio-description',
                 'description' => 'Audio Description',
             ],
        'fa-volume-control-phone' =>
             [
                 'fa-icon' => 'fas fa-phone-volume',
                 'description' => 'Volume Control Phone',
             ],
        'fa-braille' =>
             [
                 'fa-icon' => 'fas fa-braille',
                 'description' => 'Braille',
             ],
        'fa-assistive-listening-systems' =>
             [
                 'fa-icon' => 'fas fa-assistive-listening-systems',
                 'description' => 'Assistive Listening Systems',
             ],
        'fa-american-sign-language-interpreting' =>
             [
                 'fa-icon' => 'fas fa-american-sign-language-interpreting',
                 'description' => 'American Sign Language Interpreting',
             ],
        'fa-deaf' =>
             [
                 'fa-icon' => 'fas fa-deaf',
                 'description' => 'Deaf',
             ],
        'fa-glide' =>
             [
                 'fa-icon' => 'fab fa-glide',
                 'description' => 'Glide',
             ],
        'fa-glide-g' =>
             [
                 'fa-icon' => 'fab fa-glide-g',
                 'description' => 'Glide G',
             ],
        'fa-sign-language' =>
             [
                 'fa-icon' => 'fas fa-sign-language',
                 'description' => 'Sign Language',
             ],
        'fa-low-vision' =>
             [
                 'fa-icon' => 'fas fa-low-vision',
                 'description' => 'Low Vision',
             ],
        'fa-viadeo' =>
             [
                 'fa-icon' => 'fab fa-viadeo',
                 'description' => 'Viadeo',
             ],
        'fa-viadeo-square' =>
             [
                 'fa-icon' => 'fab fa-viadeo-square',
                 'description' => 'Viadeo Square',
             ],
        'fa-snapchat' =>
             [
                 'fa-icon' => 'fab fa-snapchat',
                 'description' => 'Snapchat',
             ],
        'fa-snapchat-ghost' =>
             [
                 'fa-icon' => 'fab fa-snapchat-ghost',
                 'description' => 'Snapchat Ghost',
             ],
        'fa-pied-piper' =>
             [
                 'fa-icon' => 'fab fa-pied-piper',
                 'description' => 'Pied Piper Logo',
             ],
        'fa-first-order' =>
             [
                 'fa-icon' => 'fab fa-first-order',
                 'description' => 'First Order',
             ],
        'fa-yoast' =>
             [
                 'fa-icon' => 'fab fa-yoast',
                 'description' => 'Yoast',
             ],
        'fa-themeisle' =>
             [
                 'fa-icon' => 'fab fa-themeisle',
                 'description' => 'ThemeIsle',
             ],
        'fa-google-plus-official' =>
             [
                 'fa-icon' => 'fab fa-google-plus',
                 'description' => 'Google Plus Official',
             ],
        'fa-font-awesome' =>
             [
                 'fa-icon' => 'fab fa-font-awesome',
                 'description' => 'Font Awesome',
             ],
        'fa-handshake-o' =>
             [
                 'fa-icon' => 'far fa-handshake',
                 'description' => 'Handshake Outlined',
             ],
        'fa-envelope-open' =>
             [
                 'fa-icon' => 'fas fa-envelope-open',
                 'description' => 'Envelope Open',
             ],
        'fa-envelope-open-o' =>
             [
                 'fa-icon' => 'far fa-envelope-open',
                 'description' => 'Envelope Open Outlined',
             ],
        'fa-linode' =>
             [
                 'fa-icon' => 'fab fa-linode',
                 'description' => 'Linode',
             ],
        'fa-address-book' =>
             [
                 'fa-icon' => 'fas fa-address-book',
                 'description' => 'Address Book',
             ],
        'fa-address-book-o' =>
             [
                 'fa-icon' => 'far fa-address-book',
                 'description' => 'Address Book Outlined',
             ],
        'fa-address-card' =>
             [
                 'fa-icon' => 'fas fa-address-card',
                 'description' => 'Address Card',
             ],
        'fa-address-card-o' =>
             [
                 'fa-icon' => 'far fa-address-card',
                 'description' => 'Address Card Outlined',
             ],
        'fa-user-circle' =>
             [
                 'fa-icon' => 'fas fa-user-circle',
                 'description' => 'User Circle',
             ],
        'fa-user-circle-o' =>
             [
                 'fa-icon' => 'far fa-user-circle',
                 'description' => 'User Circle Outlined',
             ],
        'fa-id-badge' =>
             [
                 'fa-icon' => 'fas fa-id-badge',
                 'description' => 'Identification Badge',
             ],
        'fa-id-card' =>
             [
                 'fa-icon' => 'fas fa-id-card',
                 'description' => 'Identification Card',
             ],
        'fa-id-card-o' =>
             [
                 'fa-icon' => 'far fa-id-card',
                 'description' => 'Identification Card Outlined',
             ],
        'fa-quora' =>
             [
                 'fa-icon' => 'fab fa-quora',
                 'description' => 'Quora',
             ],
        'fa-free-code-camp' =>
             [
                 'fa-icon' => 'fab fa-free-code-camp',
                 'description' => 'Free Code Camp',
             ],
        'fa-telegram' =>
             [
                 'fa-icon' => 'fab fa-telegram',
                 'description' => 'Telegram',
             ],
        'fa-thermometer-full' =>
             [
                 'fa-icon' => 'fas fa-thermometer-full',
                 'description' => 'Thermometer Full',
             ],
        'fa-thermometer-three-quarters' =>
             [
                 'fa-icon' => 'fas fa-thermometer-three-quarters',
                 'description' => 'Thermometer 3/4 Full',
             ],
        'fa-thermometer-half' =>
             [
                 'fa-icon' => 'fas fa-thermometer-half',
                 'description' => 'Thermometer 1/2 Full',
             ],
        'fa-thermometer-quarter' =>
             [
                 'fa-icon' => 'fas fa-thermometer-quarter',
                 'description' => 'Thermometer 1/4 Full',
             ],
        'fa-thermometer-empty' =>
             [
                 'fa-icon' => 'fas fa-thermometer-empty',
                 'description' => 'Thermometer Empty',
             ],
        'fa-shower' =>
             [
                 'fa-icon' => 'fas fa-shower',
                 'description' => 'Shower',
             ],
        'fa-bath' =>
             [
                 'fa-icon' => 'fas fa-bath',
                 'description' => 'Bath',
             ],
        'fa-podcast' =>
             [
                 'fa-icon' => 'fas fa-podcast',
                 'description' => 'Podcast',
             ],
        'fa-window-maximize' =>
             [
                 'fa-icon' => 'fas fa-window-maximize',
                 'description' => 'Window Maximize',
             ],
        'fa-window-minimize' =>
             [
                 'fa-icon' => 'fas fa-window-minimize',
                 'description' => 'Window Minimize',
             ],
        'fa-window-restore' =>
             [
                 'fa-icon' => 'fas fa-window-restore',
                 'description' => 'Window Restore',
             ],
        'fa-window-close' =>
             [
                 'fa-icon' => 'fas fa-window-close',
                 'description' => 'Window Close',
             ],
        'fa-window-close-o' =>
             [
                 'fa-icon' => 'far fa-window-close',
                 'description' => 'Window Close Outline',
             ],
        'fa-bandcamp' =>
             [
                 'fa-icon' => 'fab fa-bandcamp',
                 'description' => 'Bandcamp',
             ],
        'fa-grav' =>
             [
                 'fa-icon' => 'fab fa-grav',
                 'description' => 'Grav',
             ],
        'fa-etsy' =>
             [
                 'fa-icon' => 'fab fa-etsy',
                 'description' => 'Etsy',
             ],
        'fa-imdb' =>
             [
                 'fa-icon' => 'fab fa-imdb',
                 'description' => 'IMDB',
             ],
        'fa-ravelry' =>
             [
                 'fa-icon' => 'fab fa-ravelry',
                 'description' => 'Ravelry',
             ],
        'fa-eercast' =>
             [
                 'fa-icon' => 'fab fa-sellcast',
                 'description' => 'Sellcast',
             ],
        'fa-microchip' =>
             [
                 'fa-icon' => 'fas fa-microchip',
                 'description' => 'Microchip',
             ],
        'fa-snowflake-o' =>
             [
                 'fa-icon' => 'far fa-snowflake',
                 'description' => 'Snowflake Outlined',
             ],
        'fa-superpowers' =>
             [
                 'fa-icon' => 'fab fa-superpowers',
                 'description' => 'Superpowers',
             ],
        'fa-wpexplorer' =>
             [
                 'fa-icon' => 'fab fa-wpexplorer',
                 'description' => 'WPExplorer',
             ],
        'fa-meetup' =>
             [
                 'fa-icon' => 'fab fa-meetup',
                 'description' => 'Meetup',
             ],
    ];

    public static function getAllowedIconsJSON(): string
    {
        return json_encode(self::ALLOWED_ICONS, JSON_THROW_ON_ERROR);
    }

    public static function isValidIcon(string $icon_id): bool
    {
        return isset(self::ALLOWED_ICONS[$icon_id]);
    }

    public static function getFontAwesomeIconFromID(string $icon_id): ?string
    {
        return self::ALLOWED_ICONS[$icon_id]['fa-icon'] ?? null;
    }
}
