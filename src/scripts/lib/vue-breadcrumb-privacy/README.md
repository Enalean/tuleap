# @tuleap/vue-breadcrumb-privacy

Provides a breadcrumb component to display the project's Privacy flags.

## Usage:

```vue
<template>
    <breadcrumb-privacy
        v-bind:project_flags="project_flags"
        v-bind:privacy="privacy"
        v-bind:project_public_name="project_public_name"
    />
</template>
<script>
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";

export default {
  name: "MyComponent",
  components: [BreadcrumbPrivacy]
};
</script>
```

```typescript
import { getProjectPrivacyIcon, ProjectFlag, ProjectPrivacy } from "@tuleap/vue-breadcrumb-privacy";

const project_flag: ProjectFlag = {
  //...
};
const privacy: ProjectPrivacy = {
  //...
};
const icon = getProjectPrivacyIcon(privacy);
```
