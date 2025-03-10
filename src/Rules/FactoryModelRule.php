<?php

namespace WebWhales\CodeQualityTools\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function preg_match;
use function preg_replace;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Class_>
 */
class FactoryModelRule implements Rule
{
    public function __construct(private bool $testDocBlock, private bool $testProperty) { }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->extends?->toString() !== 'Illuminate\\Database\\Eloquent\\Factories\\Factory' || $node->isAbstract()) {
            return [];
        }

        $errors = [];

        $factoryModelName = preg_replace('/Factory$/', '', $node->name->toString());
        $actualModelName  = $this->getActualModelName($node) ?: $factoryModelName;

        if ($this->testDocBlock) {
            $this->testDockBlock($node, $factoryModelName, $actualModelName, $errors);
        }

        if ($this->testProperty) {
            $this->testProperty($node, $factoryModelName, $actualModelName, $errors);
        }

        return $errors;
    }

    private function getActualModelName(Node|Class_ $node): ?string
    {
        $property = $node->getProperty('model');

        if (! $property) {
            return null;
        }

        /** @var \PhpParser\Node\Expr\ClassConstFetch|null $defaultValue */
        $defaultValue = $property->props[0]?->default;
        $defaultValue = $defaultValue?->class;

        if (! $defaultValue) {
            return null;
        }

        $parts = $defaultValue->getParts() ?? explode('\\', $defaultValue->name);

        return array_slice($parts, -1)[0];
    }

    private function testDockBlock(
        Class_ $node,
        string $factoryModelName,
        string $actualModelName,
        array  &$errors
    ): void {
        $docComment = $node->getDocComment();

        $patternActualModel =
            "/@extends (\\\\Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\)?Factory<.+\\\\$actualModelName>/";
        $patternFactoryModel =
            "/@extends (\\\\Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\)?Factory<.+\\\\$factoryModelName>/";

        if (! $docComment || ! preg_match($patternActualModel, $docComment->getText())) {
            $errors[] = RuleErrorBuilder::message(
                $docComment && preg_match($patternFactoryModel, $docComment->getText())
                    ?
                    "Factory class has a doc block for the wrong model. Use \"@extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<App\\Models\\$actualModelName>\" instead."
                    :
                    "Factory class should have a doc block with \"@extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<App\\Models\\$actualModelName>\"."
            )
                ->identifier('factories.modelDocBlock')
                ->build();
        }
    }

    private function testProperty(
        Node|Class_ $node,
        string      $factoryModelName,
        string      $actualModelName,
        array       &$errors
    ): void {
        $property = $node->getProperty('model');

        if (! $property || $factoryModelName !== $actualModelName) {
            return;
        }

        $errors[] = RuleErrorBuilder::message('The factory class\'s model property can be omitted.')
            ->identifier('factories.modelProperty')
            ->line($property->getLine())
            ->build();
    }
}
