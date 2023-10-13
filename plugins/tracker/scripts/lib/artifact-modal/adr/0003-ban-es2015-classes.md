# Soft ban on ES2015 classes

* Status: accepted
* Deciders: Joris MASSON
* Date: 2022-01-26

Technical Story: [request #34714 Soft ban on ES2015 classes in the artifact modal][0]

## Context and Problem Statement

ES2015 opened the possibility of creating Classes in JavaScript through the `class` keyword. We have used them several times: `@tuleap/tlp-modal`, `@tuleap/drag-and-drop`, `@tuleap/list-picker`, Class component libraries for Vue 2, etc. Classes are familiar in many languages and are easier to grasp than prototypal inheritance (if you need inheritance).

However, classes have several downsides. Using classes automatically brings with it usage of `this`. It is mandatory in the constructor, in methods accessing properties, in methods calling other methods, etc. In JavaScript and TypeScript, the `this` keyword is difficult to get right. It is often a "trap", a source of mistakes for developers that don't have a lot of experience. It is often the source of errors like `TypeError: this.hide is not a function`. See the dedicated [MDN page][4] for more details. Classes also push a lot of additional syntax to learn: private class features with `#`, static class fields, static initialization blocks, decorator annotations, mixin patterns, etc. Also see [this paragraph on the drawbacks of Classes by the React Team][5] (even though we don't use React).

Since we almost never use inheritance, the main benefits of classes are encapsulation of data and bringing methods close to the data on which they work. We could achieve the same results by using plain old objects and functions.

Should we keep using classes in TypeScript or should we stop?

## Decision Outcome

In the context of the Artifact Modal, we decided to place a **soft ban** on using classes. It is "soft" because we have not configured any automated tool to enforce the ban (no `eslint` rule).

Instead of using classes, we can use types, plain objects and functions. We can describe the type of our object in TypeScript and write a function returning an object of this type. The function is like a constructor. Things that are part of the returned object are public, things that aren't included in this object are private. We can call this pattern "class-like".

```typescript
// Instead of writing this:
export class LinkFieldController {
    constructor(
        private readonly parents_retriever: RetrievePossibleParents,
        private readonly event_dispatcher: DispatchEvents,
        private readonly current_tracker_identifier: CurrentTrackerIdentifier,
    ) {}

    private dispatchFault(fault: Fault): void {
        this.event_dispatcher.dispatch(WillNotifyFault(fault));
    }

    public getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>> {
        return this.parents_retriever.getPossibleParents(this.current_tracker_identifier).match(
            (possible_parents) => possible_parents,
            (fault) => {
                this.dispatchFault(fault);
                return [];
            },
        );
    }
}
````

```typescript
// We can write this:
// Describe the public methods in a type or interface. We can reuse the same name as the function below.
export type LinkFieldController = {
    getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>>;
};

// This function is like the constructor for LinkFieldController
export const LinkFieldController = (
    // Pass parameters, just like you would for a constructor
    parents_retriever: RetrievePossibleParents,
    event_dispatcher: DispatchEvents,
    current_tracker_identifier: CurrentTrackerIdentifier,
): LinkFieldController => {
    // Functions outside of the returned object are private
    const dispatchFault = (fault: Fault): void =>
        // You can use parameters from the constructor directly. No need for `this`
        event_dispatcher.dispatch(WillNotifyFault(fault));

    return {
        // All the methods in the returned object are public
        getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>> {
            return parents_retriever.getPossibleParents(current_tracker_identifier).match(
                (possible_parents) => possible_parents,
                (fault) => {
                    // You can call private functions directly. No need for `this`
                    dispatchFault(fault);
                    return [];
                },
            );
        },
    };
};
```

We can still have an equivalent to static methods:

```typescript
export type LinkType = {
    readonly shortname: string;
    readonly direction: "forward" | "reverse";
    readonly label: string;
}

export const LinkType = {
    // This is equivalent to a `static` method. To call it, write `LinkType.buildUntyped()`
    buildUntyped: (): LinkType => ({
        shortname: "",
        direction: "forward",
        label: "is Linked to"
    }),
};
```

### Recommendations and rules

* Avoid using `class` and `this`. Use plain objects and functions instead.
* For "business objects", you should add a "constructor" function right away, otherwise it becomes harder to add it later (for example `LinkType` above is used a lot, adding methods to it is hard now).
* Test Builders are an exception to the soft ban and are allowed to use `class`. It is too difficult to write them without classes, because the essence of this pattern is to leverage methods returning `this`.

### Positive Consequences

* Usages of `this` are almost completely eliminated. Using `this` is completely optional. You can still use it, but for almost all cases, the code can be changed to completely avoid `this`. There are no more traps between `function() {}` and `() => {}` arrow functions. There are no more traps with "binding" (see [this example][6]) for callbacks, `setTimeout`, etc.
* No inheritance by default.
* Less syntax to learn → better mastery.
* Plain objects and functions are easier to minify than classes, which leads to smaller asset weights. Smaller assets are better, because they are downloaded and parsed faster, which makes Tuleap frontend faster.

    ```typescript
    export class LinkType {
      constructor(
        readonly shortname: string,
        readonly direction: "forward" | "reverse",
        readonly label: string,
      ) {}
    }
    ```

    The class above minifies to (see [on bundlejs][7]):

    ```javascript
    var r=class{constructor(o,a,n){this.shortname=o;this.direction=a;this.label=n}};export{r as LinkType};
    ```

    ```typescript
    export type LinkType = {
      readonly shortname: string;
      readonly direction: "forward" | "reverse";
      readonly label: string;
    };

    export const LinkType = (
      shortname: string,
      direction: "forward" | "reverse",
      label: string,
    ): LinkType => ({ shortname, direction, label });
    ```

    The class-like above minifies to (see [on bundlejs][8]):

    ```javascript
    var o=(r,e,n)=>({shortname:r,direction:e,label:n});export{o as LinkType};
    ```

### Negative Consequences

* People might not be familiar with this style. A lot of people are using classes, so newcomers to our codebase could be surprised (hence this ADR).
* Some web APIs (like custom elements) require using classes. However, there are ways to work around this. Using functions and plain objects was one of the reasons why we chose `hybrids` in [ADR-0001 Choice of templating engine][2].
* We cannot use `instanceof` to check whether an object matches a class. This is probably a good thing though: distinguishing objects based on their behaviour rather than their class is a better practice.
* Repetition of type: for each method, you must write its signature in the type, and repeat its name, parameters and return type in the "class-like". Once the type of parameters is written in the type, it can be omitted in the "class-like" though.

## Links

* Gregory Barkans' 2018 blog post [Should You Use Classes in JavaScript][1]
* [Classes confuse both people and machines][5] by the React Team
* [`this` keyword on MDN][4]
* [TypeScript Playground][6] illustrating one of the binding problems that often arise when using classes and `this`
* More details about the different ways `this` can be bound in [JavaScript-Garden][9]
* bundlejs [example of minifying a class][7]
* bundlejs [example of minifying a class-like][8]

[0]: https://tuleap.net/plugins/tracker/?aid=34714
[1]: https://medium.com/@vapurrmaid/should-you-use-classes-in-javascript-82f3b3df6195
[2]: <./0001-choice-of-templating-engine.md>
[4]: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/this
[5]: https://legacy.reactjs.org/docs/hooks-intro.html#classes-confuse-both-people-and-machines
[6]: https://www.typescriptlang.org/play?#code/MYGwhgzhAEBKCmBzAruATgYXFaBvAUNEdALbwAuAFgPYAmAFAJR6HFtUCWEAdAA5ocAbmHLwAshRoNGAblZEAvvnnR+QkfFUDhoiVTpMWbNsGoA7CNRDxuIaonoAiACLVolsu-LIAZj8eyKkpK+KYW5NCgkBAA+hzhYGbAmgC80GbwAO5wSKhgmNgQTHKh5hAR1ABGAFbQaQRs-NS8AFyRhXEJSTZk+rQANPgKcgD0I9AAKpRc0JkcICDQVGjU2YnQ8GgraNCOnDxqOuKSdNAzZtQRYNA+yEnkHOaO-dCV8MBgyBCae9MQjmcYBcIpVqHdaEs3FVqvhoXwVrxisowuVoFhogAZDgAa1S0EMKQAfEZiCiIocNHopHV8cwiSTjJEylYbHYHC43B5NOVfP5AmwQmw0BRkGgzAzjL0pG0CcSGozjBTdCdpHIFdAlAKhsiyhEolAQDj4J1yolkjT0VAsbikaVwtBLpRNjFoTT5UQmq12tFDbiTeQzT0VYNhvgxpM-rNqGhsTBeJsfO9yCAAJ6zeALWFUZ1wz3FIA
[7]: https://bundlejs.com/?text=%22export+class+LinkType+%7B%5Cn++constructor%28%5Cn++++readonly+shortname%3A+string%2C%5Cn++++readonly+direction%3A+%5C%22forward%5C%22+%7C+%5C%22reverse%5C%22%2C%5Cn++++readonly+label%3A+string%2C%5Cn++%29+%7B%7D%5Cn%7D%22
[8]: https://bundlejs.com/?share=KYDwDg9gTgLgBDAnmYcAyBLAdgawCrKoC8cA3gFBxxTACGAJhFgDaJwDOAFtDFrQLbAAXBxhRsAcwDclanUYs29DDQDGMDExEAiAGbQA7rSj1tcAD5xtNAG7Ao7YNplUaDJqzjNaAI2DMRdjFJGQBfGXJQSFg4VSYg9Gx8QjgSAApZLh4%2BQUDgrAkAGlllNQ0tK30oIxMzS2tgOwcnYqpvPwDRcQLigEoRTFwCFFSAPjg00g5uWBzgQrhS4HVNLAX2-zhQ3qkgA
[9]: https://shamansir.github.io/JavaScript-Garden/#function.this
