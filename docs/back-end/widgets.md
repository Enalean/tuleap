# Widgets

Widgets allow to display content on Dashboards. They can be made either
for personal dashboard, project dashboard or both.

Widget concept is rather old in Tuleap but they got a major rewamp in
Spring 2017. While most of the architecture was cleaned up some oddities
are still inherited from the past (mainly comes from
`Widget` class inheritance).

## Basic widget: "Welcome message"

The most basic widget you might imagine is the Welcome message:

-   It comes from Core
-   It's only static text
-   No interactions, no preferences.

### Step 1: Widget class

All widgets must inherit from `Widget` class and have a
`NAME` constant. Most of the time name is the 'slug'
version of the class name: `MyWelcomeMessage` =>
`mywelcomemessage`. Then `NAME` is important
because this is the identifier that will be stored in database
(`dashboards_lines_columns_widgets`).

it's a bad idea to change `NAME` after integration as you
will have to write a forgeupgrade migration bucket otherwise the widget
will disappear from all dashboards.

The convention is to prefix personal widgets with "MyXXX" and project
widgets with "ProjectXXX".

You create the MyWelcomeMessage.php file in src/common/widget with
following content:

``` php
/** src/common/widget/MyWelcomeMessage.php */
<?php

declare(strict_types=1);

namespace Tuleap\Widget;

use Widget;

class MyWelcomeMessage extends Widget
{
    public const NAME = 'mywelcomemessage';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle(): string
    {
        return _('Welcome aboard');
    }

    public function getDescription(): string
    {
        return _('Welcome message and information for users');
    }

    public function getContent(): string
    {
        return 'Welcome';
    }
}
```

and run `make composer` to refresh autoload files.

### Step 2: Reference the widget

The widget must be referenced in 2 places:

-   `src/common/widget/WidgetFactory.php` in
    `getInstanceByWidgetName` method

    ``` php
    ...
    case MyWelcomeMessage::NAME:
        $widget = new MyWelcomeMessage($user);
        break;
    ...
    ```

-   `src/common/Dashboard/Widget/Add/AddWidget/Controller.php`
    in `getWidgetsForOwnerType` method according to widget
    type (project or personal)

    ``` php
    switch ($owner_type) {
        case UserDashboardController::LEGACY_DASHBOARD_TYPE:
            $widgets = [
                ...
                MyWelcomeMessage::NAME,
            ];
            break;
        ...
    }
    ```

You can now add a new widget on your personal page, it will display a
simple message within.

## Javascript and CSS dependencies

It's becoming common to have complicated widgets with lots of user
interaction. When you need to include
[Javascript files](./../front-end/javascript.md) or
[CSS files](./../front-end/css.md), you'll need to
override those two functions:

``` php
/** src/common/widget/MyWelcomeMessage.php */
public function getJavascriptDependencies(): array
{
    $assets = $this->getAssets();
    return [
        ['file' => $assets->getFileURL('angular.js'), 'unique-name' => 'angular'],
        ['snippet' => 'window.CKEDITOR_BASEPATH = "' . $ckeditor_path . '";'],
        ['file' => $ckeditor_path . 'ckeditor.js'],
        ['file' => $assets->getFileURL('kanban.js')],
    ];
}

public function getAssets(): IncludeAssets
{
    return new IncludeAssets(
        __DIR__. '/../../www/assets/agiledashboard',
        '/assets/agiledashboard'
    );
}
```

The previous code block shows an example with the Kanban widget. It
returns an array of arrays. Each array must have either a 'file' key
or a 'snippet' key. 'file' keys *can* have a 'unique-name'.
'unique-name' files will be included only once for all widgets present
on the page.

``` php
/** src/common/widget/MyWelcomeMessage.php */
public function getStylesheetDependencies(): CssAssetCollection
{
    return new CssAssetCollection([new CssAsset($this->getAssets(), 'kanban')]);
}
```

The previous code block shows an example, again with the Kanban widget.
It returns a `CssAssetCollection` object which helps to deduplicate CSS
files. That way, if there are two identical widgets on the same
dashboard, their CSS will be loaded only once.
