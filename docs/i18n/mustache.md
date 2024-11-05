# i18n in Mustache templates

In order to not pollute your presenters, you should use gettext directly
in the `.mustache` files:

``` html
<!-- In core -->
<h1>{{# gettext }}Personal page{{/ gettext }}</h1>

<!-- With variables -->
<p>{{# gettext }}It's likely that %s will see %s.| {{ username }} | {{ label }} {{/ gettext }}</p>

<!-- Plurals-->
<p>
  {{# ngettext }}
    There is %s apple
    | There are %s apples
    | {{ count }}
  {{/ ngettext }} <!-- There are 2 apples -->
</p>

<p>
  {{# ngettext }}
    The user with id %s has been removed from %s
    | The users with id [%s] have been removed from %s
    | {{ count }}
    | {{ comma_separated_ids }}
    | {{ project_name }}
  {{/ ngettext }} <!-- The users with id [123, 456] have been removed from GuineaPig -->
</p>

<!-- The same in plugins by giving the domain with dgettext and dngettext -->
<h1>{{# dgettext }} tuleap-agiledashboard | Scrum backlog {{/ dgettext }}
```

As we are using `|` as separator, you cannot use it in your strings (and
there is no way to escape it for now, contribution welcomed if you
really need it).
