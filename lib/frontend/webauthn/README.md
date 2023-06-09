# @tuleap/webauthn

This little library brings only one function:

```ts
function authenticate(): ResultAsync<null, Fault>
```

This function allows you to ask users to authenticate with their passkey before doing an action. It will returns `nothing`
if authentication succeeds, `Fault` otherwise. There is just one special case, if user have no registered passkey it
returns `nothing` to avoid blocking the user.

*Example*

```ts
import { authenticate } from "@tuleap/webauthn";

button.addEventListener("click", () => {
  authenticate().match(
    () => {
      // Perform action
    },
    (fault) => {
      // Display fault
    }
  );
});
```
