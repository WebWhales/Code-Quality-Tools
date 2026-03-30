<?php

namespace WebWhales\CodeQualityTools\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use function dump;
use function preg_match;
use function preg_replace;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Class_>
 */
class FactoryModelRule implements Rule
{
    public function __construct(
        private bool                         $testDocBlock,
        private bool                         $testProperty,
        private \PHPStan\Type\FileTypeMapper $fileTypeMapper
    )
    {
    }

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

        $factoryModelName = 'App\Models\\' . preg_replace('/Factory$/', '', $node->name->toString());
        $actualModelName  = $this->getActualModelName($node) ?: $factoryModelName;

        if ($this->testDocBlock) {
            $this->testDockBlock($node, $scope, $actualModelName, $errors);
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

        return $defaultValue->name;
    }

    private function testDockBlock(
        Class_ $node,
        Scope  $scope,
        string $actualModelName,
        array  &$errors
    ): void {
        $docComment = $node->getDocComment();

        $hasCorrectModel = false;
        $hasAnyModel     = false;

        if ($docComment) {
            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $node->namespacedName->name,
                null,
                null,
                $docComment->getText()
            );

            $extendsTags = $resolvedPhpDoc->getExtendsTags();

            foreach ($extendsTags as $extendsTag) {
                $type = $extendsTag->getType();

                if (! $type instanceof GenericObjectType || $type->getClassName() !== 'Illuminate\Database\Eloquent\Factories\Factory') {
                    continue;
                }

                $types = $type->getTypes();

                if (empty($types)) {
                    continue;
                }

                $hasAnyModel = true;

                if ($types[0] instanceof ObjectType) {
                    $className = ltrim($types[0]->getClassName(), '\\');
                    if ($className === $actualModelName) {
                        $hasCorrectModel = true;
                    }
                }
            }
        }

        if (! $hasCorrectModel) {
            $errors[] = RuleErrorBuilder::message(
                $hasAnyModel
                    ?
                    "Factory class has a doc block for the wrong model. Use \"@extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\$actualModelName>\" instead."
                    :
                    "Factory class should have a doc block with \"@extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\$actualModelName>\"."
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
