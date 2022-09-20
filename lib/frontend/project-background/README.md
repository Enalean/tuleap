# @tuleap/project-background

## Usage

A SCSS library to share Project Background. It is used both in FlamingParrot and BurningParrot themes.

```scss
@use "@tuleap/project-background";
@use "../../src/themes/BurningParrot/css/includes/global-variables";

.project-with-background {
    .my-header-class-that-must-show-project-background {
        // Change the text color depending on the chosen image
        @include project-background.title-header-typography;
    }
}

body {
    @include project-background.apply-background(
        ".my-header-class-that-must-show-project-background",
        global-variables.$sidebar-expanded-width,
        global-variables.$sidebar-collapsed-width
    );
}
```
