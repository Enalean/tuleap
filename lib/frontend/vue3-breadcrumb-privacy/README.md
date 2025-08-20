# @tuleap/vue3-breadcrumb-privacy

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
```

```typescript
import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";

const project_flag: ProjectFlag = {
  //...
};
```
