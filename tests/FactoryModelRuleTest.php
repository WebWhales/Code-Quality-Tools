<?php

namespace WebWhales\CodeQualityTools\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use WebWhales\CodeQualityTools\Rules\FactoryModelRule;

#[CoversClass(FactoryModelRule::class)]
class FactoryModelRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new FactoryModelRule(true, true);
    }

    public function test_rule(): void
    {
        // first argument: path to the example file that contains some errors that should be reported by MyRule
        // second argument: an array of expected errors,
        // each error consists of the asserted error message, and the asserted error file line
        $this->analyse(
            [__DIR__ . '/assets/rules/factory-extends-doc-block/factory-class.php.stub'],
            [
                [
                    // asserted error message
                    'Factory class should have a doc block with "@extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Example>".',
                    // asserted error line
                    8,
                ],
                [
                    'The factory class\'s model property can be omitted.',
                    15,
                ],
            ]);
    }

    public function test_rule_for_factory_with_docblock(): void
    {
        // first argument: path to the example file that contains some errors that should be reported by MyRule
        // second argument: an array of expected errors,
        // each error consists of the asserted error message, and the asserted error file line
        $this->analyse(
            [__DIR__ . '/assets/rules/factory-extends-doc-block/factory-class-with-docblock.php.stub'],
            [
                [
                    'The factory class\'s model property can be omitted.', // asserted error message
                    18, // asserted error line
                ],
            ]);
    }

    public function test_rule_for_factory_for_another_model(): void
    {
        // first argument: path to the example file that contains some errors that should be reported by MyRule
        // second argument: an array of expected errors,
        // each error consists of the asserted error message, and the asserted error file line
        $this->analyse(
            [__DIR__ . '/assets/rules/factory-extends-doc-block/factory-class-for-another-model.php.stub'],
            [
                [
                    'Factory class should have a doc block with "@extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnotherModel>".',
                    8,
                ],
            ]);
    }

    public function test_rule_for_factory_for_another_model_and_incorrect_docblock(): void
    {
        // first argument: path to the example file that contains some errors that should be reported by MyRule
        // second argument: an array of expected errors,
        // each error consists of the asserted error message, and the asserted error file line
        $this->analyse(
            [__DIR__ . '/assets/rules/factory-extends-doc-block/factory-class-for-another-model-with-incorrect-docblock.php.stub'],
            [
                [
                    'Factory class has a doc block for the wrong model. Use "@extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnotherModel>" instead.',
                    11,
                ],
            ]);
    }
}
