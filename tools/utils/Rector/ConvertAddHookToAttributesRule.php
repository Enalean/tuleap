<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapDev\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ConvertAddHookToAttributesRule extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $constructor = $node->getMethod('__construct');
        if ($constructor) {
            $this->extractHooksFrom($node, $constructor);
        }
        $hooks_and_callbacks = $node->getMethod('getHooksAndCallbacks');
        if ($hooks_and_callbacks) {
            $this->extractHooksFrom($node, $hooks_and_callbacks);
        }

        return $node;
    }

    private function extractHooksFrom(Node $node, ClassMethod $method)
    {
        foreach ($method->stmts as $k => $stmt) {
            $add_hook_call = $this->getAddHookCall($stmt);
            if (! $add_hook_call) {
                continue;
            }

            $callback_and_attribute = $this->getAddHookCallback($add_hook_call);
            if (! $callback_and_attribute) {
                continue;
            }

            $actual_callback = $this->findCallBackMethod($node, $callback_and_attribute);
            if (! $actual_callback) {
                continue;
            }

            $this->enhanceCallback($actual_callback, $callback_and_attribute);
            unset($method->stmts[$k]);
        }
    }

    private function getAddHookCall(Node $stmt): ?Node\Expr\MethodCall
    {
        if (
            $stmt instanceof Node\Stmt\Expression &&
            $stmt->expr instanceof Node\Expr\MethodCall &&
            $stmt->expr->var instanceof Node\Expr\Variable &&
            (string) $stmt->expr->name === 'addHook'
        ) {
            return $stmt->expr;
        }
        return null;
    }

    private function getAddHookCallback(Node\Expr\MethodCall $add_hook_call): ?CallbackAndAttribute
    {
        return match (count($add_hook_call->args)) {
            1 => $this->getAddHookWithSingleArgumentCallback($add_hook_call->args[0]),
            2 => $this->getAddHookWhenCallbackIsSet($add_hook_call->args[0], $add_hook_call->args[1]),
            default => null,
        };
    }

    private function getAddHookWithSingleArgumentCallback(Node\Arg $arg): ?CallbackAndAttribute
    {
        if ($arg->value instanceof Node\Expr\ClassConstFetch) {
            return $this->getCallbackFromConstantFetch($arg->value);
        } elseif ($arg->value instanceof Node\Scalar\String_) {
            return new CallbackAndAttributeName(
                $arg->value->value,
                $arg->value,
            );
        }
        return null;
    }

    private function getAddHookWhenCallbackIsSet(Node\Arg $hook, Node\Arg $callback): ?CallbackAndAttribute
    {
        if ($callback->value instanceof Node\Scalar\String_ && ($hook->value instanceof Node\Scalar\String_ || $hook->value instanceof Node\Expr\ClassConstFetch)) {
            return new CallbackAndAttributeName(
                $callback->value->value,
                $hook->value,
            );
        }
        return null;
    }

    private function getCallbackFromConstantFetch(Node\Expr\ClassConstFetch $constant_fetch): ?CallbackAndAttribute
    {
        if (! $constant_fetch->class instanceof Node\Name\FullyQualified) {
            return null;
        }
        if (! $constant_fetch->name instanceof Node\Identifier) {
            return null;
        }
        if ((string) $constant_fetch->name === 'NAME') {
            return new CallbackAndAttributeClass(
                $this->getCallbackFromNameBasedHook($constant_fetch->class, $constant_fetch->name),
            );
        }
        return new CallbackAndAttributeName(
            $this->getCallbackFromNameBasedHook($constant_fetch->class, $constant_fetch->name),
            $constant_fetch,
        );
    }

    private function getCallbackFromNameBasedHook(Node\Name\FullyQualified $class, Node\Identifier $const): string
    {
        $class_name      = (string) $class;
        $const_name      = (string) $const;
        $reflected_class = new \ReflectionClass($class_name);
        return $reflected_class->getConstant($const_name);
    }

    private function findCallBackMethod(Node $node, CallbackAndAttribute $callback_and_attribute): ?ClassMethod
    {
        $actual_callback = $node->getMethod($callback_and_attribute->callback);
        if ($actual_callback instanceof ClassMethod) {
            return $actual_callback;
        }
        $actual_callback = $node->getMethod($this->deduceCallbackFromHook($callback_and_attribute->hook));
        if ($actual_callback instanceof ClassMethod) {
            return $actual_callback;
        }
        return null;
    }

    /**
     * @see \Plugin::deduceCallbackFromHook()
     */
    private function deduceCallbackFromHook(string $hook): string
    {
        return lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('_', ' ', $hook)
                )
            )
        );
    }

    private function enhanceCallback(ClassMethod $method, CallbackAndAttribute $callback_and_attribute): void
    {
        $this->addAttribute($method, $callback_and_attribute->attribute);
        $this->renameToPsr1($method, $callback_and_attribute->new_callback);
        $this->addVoidReturnType($method);
    }

    private function addAttribute(ClassMethod $method, Node\Attribute $attribute): void
    {
        if (count($method->attrGroups) === 0) {
            $method->attrGroups[] = new Node\AttributeGroup([$attribute]);
        }
    }

    private function renameToPsr1(ClassMethod $method, string $new_name): void
    {
        $method->name = new Node\Identifier($new_name);
    }

    private function addVoidReturnType(ClassMethod $method): void
    {
        if ($method->returnType === null) {
            $method->returnType = new Node\Identifier('void');
        }
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert hook listening from addHook() to attributes',
            [
                new CodeSample(
                    '$this->addHook(SomeEvent::NAME)',
                    '#[\Tuleap\Plugin\ListeningToEventClass]'
                ),
            ]
        );
    }
}
