# @tuleap/project-privacy-helper

Provides some helpers (types and functions) to help you manage project privacy information.

## Usage

```typescript
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import { getProjectPrivacyIcon } from "@tuleap/project-privacy-helper";

const privacy: ProjectPrivacy = {
  //...
};
const icon = getProjectPrivacyIcon(privacy);
```
