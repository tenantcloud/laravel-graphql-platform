<?php declare(strict_types=1);

namespace Tests\Unit\Schema\Rules;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError;
use GraphQL\Language\AST\Node;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Language\Parser;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ValidationRule;
use TenantCloud\GraphQLPlatform\Schema\Rules\CustomOverlappingFieldsCanBeMerged as OverlappingFieldsCanBeMerged;
use Tests\TestCase;

final class CustomOverlappingFieldsCanBeMergedTest extends TestCase
{
	// Validate: Overlapping fields can be merged

	/**
	 * @see it('unique fields')
	 */
	public function testUniqueFields(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment uniqueFields on Dog {
        name
        nickname
      }
        '
		);
	}

	/**
	 * @see it('identical fields')
	 */
	public function testIdenticalFields(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment mergeIdenticalFields on Dog {
        name
        name
      }
        '
		);
	}

	/**
	 * @see it('identical fields with identical args')
	 */
	public function testIdenticalFieldsWithIdenticalArgs(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment mergeIdenticalFieldsWithIdenticalArgs on Dog {
        doesKnowCommand(dogCommand: SIT)
        doesKnowCommand(dogCommand: SIT)
      }
        '
		);
	}

	/**
	 * @see it('identical fields with identical directives')
	 */
	public function testIdenticalFieldsWithIdenticalDirectives(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment mergeSameFieldsWithSameDirectives on Dog {
        name @include(if: true)
        name @include(if: true)
      }
        '
		);
	}

	/**
	 * @see it('different args with different aliases')
	 */
	public function testDifferentArgsWithDifferentAliases(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment differentArgsWithDifferentAliases on Dog {
        knowsSit : doesKnowCommand(dogCommand: SIT)
        knowsDown : doesKnowCommand(dogCommand: DOWN)
      }
        '
		);
	}

	/**
	 * @see it('different directives with different aliases')
	 */
	public function testDifferentDirectivesWithDifferentAliases(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment differentDirectivesWithDifferentAliases on Dog {
        nameIfTrue : name @include(if: true)
        nameIfFalse : name @include(if: false)
      }
        '
		);
	}

	/**
	 * @see it('different skip/include directives accepted')
	 */
	public function testDifferentSkipIncludeDirectivesAccepted(): void
	{
		// Note: Differing skip/include directives don't create an ambiguous return
		// value and are acceptable in conditions where differing runtime values
		// may have the same desired effect of including or skipping a field.
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment differentDirectivesWithDifferentAliases on Dog {
        name @include(if: true)
        name @include(if: false)
      }
    '
		);
	}

	/**
	 * @see it('Same aliases with different field targets')
	 */
	public function testSameAliasesWithDifferentFieldTargets(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment sameAliasesWithDifferentFieldTargets on Dog {
        fido : name
        fido : nickname
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'fido',
						'name and nickname are different fields'
					),
					[new SourceLocation(3, 9), new SourceLocation(4, 9)]
				),
			]
		);
	}

	/**
	 * @see it('Same aliases allowed on non-overlapping fields')
	 */
	public function testSameAliasesAllowedOnNonOverlappingFields(): void
	{
		// This is valid since no object can be both a "Dog" and a "Cat", thus
		// these fields can never overlap.
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment sameAliasesWithDifferentFieldTargets on Pet {
        ... on Dog {
          name
        }
        ... on Cat {
          name: nickname
        }
      }
        '
		);
	}

	/**
	 * @see it('Alias masking direct field access')
	 */
	public function testAliasMaskingDirectFieldAccess(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment aliasMaskingDirectFieldAccess on Dog {
        name : nickname
        name
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'name',
						'nickname and name are different fields'
					),
					[new SourceLocation(3, 9), new SourceLocation(4, 9)]
				),
			]
		);
	}

	/**
	 * @see it('different args, second adds an argument')
	 */
	public function testDifferentArgsSecondAddsAnArgument(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment conflictingArgs on Dog {
        doesKnowCommand
        doesKnowCommand(dogCommand: HEEL)
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'doesKnowCommand',
						'they have differing arguments'
					),
					[new SourceLocation(3, 9), new SourceLocation(4, 9)]
				),
			]
		);
	}

	/**
	 * @see it('different args, second missing an argument')
	 */
	public function testDifferentArgsSecondMissingAnArgument(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment conflictingArgs on Dog {
        doesKnowCommand(dogCommand: SIT)
        doesKnowCommand
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'doesKnowCommand',
						'they have differing arguments'
					),
					[new SourceLocation(3, 9), new SourceLocation(4, 9)]
				),
			]
		);
	}

	/**
	 * @see it('conflicting args')
	 */
	public function testConflictingArgs(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment conflictingArgs on Dog {
        doesKnowCommand(dogCommand: SIT)
        doesKnowCommand(dogCommand: HEEL)
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'doesKnowCommand',
						'they have differing arguments'
					),
					[new SourceLocation(3, 9), new SourceLocation(4, 9)]
				),
			]
		);
	}

	/**
	 * @see it('allows different args where no conflict is possible')
	 */
	public function testAllowsDifferentArgsWhereNoConflictIsPossible(): void
	{
		// This is valid since no object can be both a "Dog" and a "Cat", thus
		// these fields can never overlap.
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment conflictingArgs on Pet {
        ... on Dog {
          name(surname: true)
        }
        ... on Cat {
          name
        }
      }
        '
		);
	}

	/**
	 * @see it('encounters conflict in fragments')
	 */
	public function testEncountersConflictInFragments(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        ...A
        ...B
      }
      fragment A on Type {
        x: a
      }
      fragment B on Type {
        x: b
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage('x', 'a and b are different fields'),
					[new SourceLocation(7, 9), new SourceLocation(10, 9)]
				),
			]
		);
	}

	/**
	 * @see it('reports each conflict once')
	 */
	public function testReportsEachConflictOnce(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        f1 {
          ...A
          ...B
        }
        f2 {
          ...B
          ...A
        }
        f3 {
          ...A
          ...B
          x: c
        }
      }
      fragment A on Type {
        x: a
      }
      fragment B on Type {
        x: b
      }
    ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage('x', 'a and b are different fields'),
					[new SourceLocation(18, 9), new SourceLocation(21, 9)]
				),
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage('x', 'c and a are different fields'),
					[new SourceLocation(14, 11), new SourceLocation(18, 9)]
				),
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage('x', 'c and b are different fields'),
					[new SourceLocation(14, 11), new SourceLocation(21, 9)]
				),
			]
		);
	}

	/**
	 * @see it('deep conflict')
	 */
	public function testDeepConflict(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          x: a
        },
        field {
          x: b
        }
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'field',
						[['x', 'a and b are different fields']]
					),
					[
						new SourceLocation(3, 9),
						new SourceLocation(4, 11),
						new SourceLocation(6, 9),
						new SourceLocation(7, 11),
					]
				),
			]
		);
	}

	/**
	 * @see it('deep conflict with multiple issues')
	 */
	public function testDeepConflictWithMultipleIssues(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          x: a
          y: c
        },
        field {
          x: b
          y: d
        }
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'field',
						[
							['x', 'a and b are different fields'],
							['y', 'c and d are different fields'],
						]
					),
					[
						new SourceLocation(3, 9),
						new SourceLocation(4, 11),
						new SourceLocation(5, 11),
						new SourceLocation(7, 9),
						new SourceLocation(8, 11),
						new SourceLocation(9, 11),
					]
				),
			]
		);
	}

	/**
	 * @see it('very deep conflict')
	 */
	public function testVeryDeepConflict(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          deepField {
            x: a
          }
        },
        field {
          deepField {
            x: b
          }
        }
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'field',
						[['deepField', [['x', 'a and b are different fields']]]]
					),
					[
						new SourceLocation(3, 9),
						new SourceLocation(4, 11),
						new SourceLocation(5, 13),
						new SourceLocation(8, 9),
						new SourceLocation(9, 11),
						new SourceLocation(10, 13),
					]
				),
			]
		);
	}

	/**
	 * @see it('reports deep conflict to nearest common ancestor')
	 */
	public function testReportsDeepConflictToNearestCommonAncestor(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          deepField {
            x: a
          }
          deepField {
            x: b
          }
        },
        field {
          deepField {
            y
          }
        }
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'deepField',
						[['x', 'a and b are different fields']]
					),
					[
						new SourceLocation(4, 11),
						new SourceLocation(5, 13),
						new SourceLocation(7, 11),
						new SourceLocation(8, 13),
					]
				),
			]
		);
	}

	/**
	 * @see it('reports deep conflict to nearest common ancestor in fragments')
	 */
	public function testReportsDeepConflictToNearestCommonAncestorInFragments(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          ...F
        }
        field {
          ...F
        }
      }
      fragment F on T {
        deepField {
          deeperField {
            x: a
          }
          deeperField {
            x: b
          }
        }
        deepField {
          deeperField {
            y
          }
        }
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'deeperField',
						[['x', 'a and b are different fields']]
					),
					[
						new SourceLocation(12, 11),
						new SourceLocation(13, 13),
						new SourceLocation(15, 11),
						new SourceLocation(16, 13),
					]
				),
			]
		);
	}

	/**
	 * @see it('reports deep conflict in nested fragments')
	 */
	public function testReportsDeepConflictInNestedFragments(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          ...F
        }
        field {
          ...I
        }
      }
      fragment F on T {
        x: a
        ...G
      }
      fragment G on T {
        y: c
      }
      fragment I on T {
        y: d
        ...J
      }
      fragment J on T {
        x: b
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'field',
						[
							['x', 'a and b are different fields'],
							['y', 'c and d are different fields'],
						]
					),
					[
						new SourceLocation(3, 9),
						new SourceLocation(11, 9),
						new SourceLocation(15, 9),
						new SourceLocation(6, 9),
						new SourceLocation(22, 9),
						new SourceLocation(18, 9),
					]
				),
			]
		);
	}

	/**
	 * @see it('ignores unknown fragments')
	 */
	public function testIgnoresUnknownFragments(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
      {
        field {
          ...Unknown
          ...Known
        }
      }
      fragment Known on T {
        field
        ...OtherUnknown
      }
        '
		);
	}

	// Describe: return types must be unambiguous

	/**
	 * @see it('conflicting return types which potentially overlap')
	 */
	public function testConflictingReturnTypesWhichPotentiallyOverlap(): void
	{
		// This is invalid since an object could potentially be both the Object
		// type IntBox and the interface type NonNullStringBox1. While that
		// condition does not exist in the current schema, the schema could
		// expand in the future to allow this. Thus it is invalid.
		$this->expectFailsRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ...on IntBox {
              scalar
            }
            ...on NonNullStringBox1 {
              scalar
            }
          }
        }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'scalar',
						'they return conflicting types Int and String!'
					),
					[
						new SourceLocation(5, 15),
						new SourceLocation(8, 15),
					]
				),
			]
		);
	}

	/**
	 * @see it('compatible return shapes on different return types')
	 */
	public function testCompatibleReturnShapesOnDifferentReturnTypes(): void
	{
		// In this case `deepBox` returns `SomeBox` in the first usage, and
		// `StringBox` in the second usage. These return types are not the same!
		// however this is valid because the return *shapes* are compatible.
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
      {
        someBox {
          ... on SomeBox {
            deepBox {
              unrelatedField
            }
          }
          ... on StringBox {
            deepBox {
              unrelatedField
            }
          }
        }
      }
        '
		);
	}

	/**
	 * @see it('disallows differing return types despite no overlap')
	 */
	public function testDisallowsDifferingReturnTypesDespiteNoOverlap(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              scalar
            }
            ... on StringBox {
              scalar
            }
          }
        }
        '
		);
	}

	/**
	 * @see it('reports correctly when a non-exclusive follows an exclusive')
	 */
	public function testReportsCorrectlyWhenANonExclusiveFollowsAnExclusive(): void
	{
		$this->expectFailsRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              deepBox {
                ...X
              }
            }
          }
          someBox {
            ... on StringBox {
              deepBox {
                ...Y
              }
            }
          }
          memoed: someBox {
            ... on IntBox {
              deepBox {
                ...X
              }
            }
          }
          memoed: someBox {
            ... on StringBox {
              deepBox {
                ...Y
              }
            }
          }
          other: someBox {
            ...X
          }
          other: someBox {
            ...Y
          }
        }
        fragment X on SomeBox {
          scalar
        }
        fragment Y on SomeBox {
          scalar: unrelatedField
        }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'other',
						[['scalar', 'scalar and unrelatedField are different fields']]
					),
					[
						new SourceLocation(31, 11),
						new SourceLocation(39, 11),
						new SourceLocation(34, 11),
						new SourceLocation(42, 11),
					]
				),
			]
		);
	}

	/**
	 * @see it('disallows differing return type nullability despite no overlap')
	 */
	public function testDisallowsDifferingReturnTypeNullabilityDespiteNoOverlap(): void
	{
		$this->expectFailsRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on NonNullStringBox1 {
              scalar
            }
            ... on StringBox {
              scalar
            }
          }
        }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'scalar',
						'they return conflicting types String! and String'
					),
					[
						new SourceLocation(5, 15),
						new SourceLocation(8, 15),
					]
				),
			]
		);
	}

	/**
	 * @see it('disallows differing return type list despite no overlap')
	 */
	public function testDisallowsDifferingReturnTypeListDespiteNoOverlap(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              box: listStringBox {
                scalar
              }
            }
            ... on StringBox {
              box: stringBox {
                scalar
              }
            }
          }
        }
        '
		);

		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              box: stringBox {
                scalar
              }
            }
            ... on StringBox {
              box: listStringBox {
                scalar
              }
            }
          }
        }
        '
		);
	}

	public function testDisallowsDifferingSubfields(): void
	{
		$this->expectFailsRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              box: stringBox {
                val: scalar
                val: unrelatedField
              }
            }
            ... on StringBox {
              box: stringBox {
                val: scalar
              }
            }
          }
        }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'val',
						'scalar and unrelatedField are different fields'
					),
					[
						new SourceLocation(6, 17),
						new SourceLocation(7, 17),
					]
				),
			]
		);
	}

	/**
	 * @see it('disallows differing deep return types despite no overlap')
	 */
	public function testDisallowsDifferingDeepReturnTypesDespiteNoOverlap(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              box: stringBox {
                scalar
              }
            }
            ... on StringBox {
              box: intBox {
                scalar
              }
            }
          }
        }
        '
		);
	}

	/**
	 * @see it('allows non-conflicting overlapping types')
	 */
	public function testAllowsNonConflictingOverlappingTypes(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ... on IntBox {
              scalar: unrelatedField
            }
            ... on StringBox {
              scalar
            }
          }
        }
        '
		);
	}

	/**
	 * @see it('same wrapped scalar return types')
	 */
	public function testSameWrappedScalarReturnTypes(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ...on NonNullStringBox1 {
              scalar
            }
            ...on NonNullStringBox2 {
              scalar
            }
          }
        }
        '
		);
	}

	/**
	 * @see it('allows inline typeless fragments')
	 */
	public function testAllowsInlineTypelessFragments(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          a
          ... {
            a
          }
        }
        '
		);
	}

	/**
	 * @see it('compares deep types including list')
	 */
	public function testComparesDeepTypesIncludingList(): void
	{
		$this->expectFailsRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          connection {
            ...edgeID
            edges {
              node {
                id: name
              }
            }
          }
        }

        fragment edgeID on Connection {
          edges {
            node {
              id
            }
          }
        }
      ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'edges',
						[['node', [['id', 'name and id are different fields']]]]
					),
					[
						new SourceLocation(5, 13),
						new SourceLocation(6, 15),
						new SourceLocation(7, 17),
						new SourceLocation(14, 11),
						new SourceLocation(15, 13),
						new SourceLocation(16, 15),
					]
				),
			]
		);
	}

	/**
	 * @see it('ignores unknown types')
	 */
	public function testIgnoresUnknownTypes(): void
	{
		$this->expectPassesRuleWithSchema(
			$this->getSchema(),
			new OverlappingFieldsCanBeMerged(),
			'
        {
          someBox {
            ...on UnknownType {
              scalar
            }
            ...on NonNullStringBox2 {
              scalar
            }
          }
        }
        '
		);
	}

	/**
	 * @see it('error message contains hint for alias conflict')
	 */
	public function testErrorMessageContainsHintForAliasConflict(): void
	{
		// The error template should end with a hint for the user to try using
		// different aliases.
		$error = OverlappingFieldsCanBeMerged::fieldsConflictMessage('x', 'a and b are different fields');
		$hint = 'Use different aliases on the fields to fetch both if this was intentional.';

		self::assertStringEndsWith($hint, $error);
	}

	/**
	 * @see it('does not infinite loop on recursive fragment')
	 */
	public function testDoesNotInfiniteLoopOnRecursiveFragment(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
        fragment fragA on Human { name, relatives { name, ...fragA } }
        '
		);
	}

	/**
	 * @see it('does not infinite loop on immediately recursive fragment')
	 */
	public function testDoesNotInfiniteLoopOnImmeditelyRecursiveFragment(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
        fragment fragA on Human { name, ...fragA }
        '
		);
	}

	/**
	 * @see it('does not infinite loop on transitively recursive fragment')
	 */
	public function testDoesNotInfiniteLoopOnTransitivelyRecursiveFragment(): void
	{
		$this->expectPassesRule(
			new OverlappingFieldsCanBeMerged(),
			'
        fragment fragA on Human { name, ...fragB }
        fragment fragB on Human { name, ...fragC }
        fragment fragC on Human { name, ...fragA }
        '
		);
	}

	/**
	 * @see it('find invalid case even with immediately recursive fragment')
	 */
	public function testFindInvalidCaseEvenWithImmediatelyRecursiveFragment(): void
	{
		$this->expectFailsRule(
			new OverlappingFieldsCanBeMerged(),
			'
      fragment sameAliasesWithDifferentFieldTargets on Dob {
        ...sameAliasesWithDifferentFieldTargets
        fido: name
        fido: nickname
      }
        ',
			[
				self::createErrors(
					OverlappingFieldsCanBeMerged::fieldsConflictMessage(
						'fido',
						'name and nickname are different fields'
					),
					[
						new SourceLocation(4, 9),
						new SourceLocation(5, 9),
					]
				),
			]
		);
	}

	public static function getTestSchema(): Schema
	{
		$Being = new InterfaceType([
			'name'   => 'Being',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'args' => ['surname' => ['type' => Type::boolean()]],
				],
			],
		]);

		$Pet = new InterfaceType([
			'name'   => 'Pet',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'args' => ['surname' => ['type' => Type::boolean()]],
				],
			],
		]);

		$Canine = new InterfaceType([
			'name'   => 'Canine',
			'fields' => static fn (): array => [
				'name' => [
					'type' => Type::string(),
					'args' => ['surname' => ['type' => Type::boolean()]],
				],
			],
		]);

		$DogCommand = new EnumType([
			'name'   => 'DogCommand',
			'values' => [
				'SIT'  => ['value' => 0],
				'HEEL' => ['value' => 1],
				'DOWN' => ['value' => 2],
			],
		]);

		$Dog = new ObjectType([
			'name'   => 'Dog',
			'fields' => [
				'name' => [
					'type' => Type::string(),
					'args' => ['surname' => ['type' => Type::boolean()]],
				],
				'nickname'        => ['type' => Type::string()],
				'barkVolume'      => ['type' => Type::int()],
				'barks'           => ['type' => Type::boolean()],
				'doesKnowCommand' => [
					'type' => Type::boolean(),
					'args' => ['dogCommand' => ['type' => $DogCommand]],
				],
				'isHousetrained' => [
					'type' => Type::boolean(),
					'args' => ['atOtherHomes' => ['type' => Type::boolean(), 'defaultValue' => true]],
				],
				'isAtLocation' => [
					'type' => Type::boolean(),
					'args' => ['x' => ['type' => Type::int()], 'y' => ['type' => Type::int()]],
				],
				'secretName' => [
					'type'    => Type::string(),
					'visible' => false,
				],
			],
			'interfaces' => [$Being, $Pet, $Canine],
		]);

		$Cat = new ObjectType([
			'name'   => 'Cat',
			'fields' => static function () use (&$FurColor): array {
				return [
					'name' => [
						'type' => Type::string(),
						'args' => ['surname' => ['type' => Type::boolean()]],
					],
					'nickname'   => ['type' => Type::string()],
					'meows'      => ['type' => Type::boolean()],
					'meowVolume' => ['type' => Type::int()],
					'furColor'   => $FurColor,
				];
			},
			'interfaces' => [$Being, $Pet],
		]);

		$CatOrDog = new UnionType([
			'name'  => 'CatOrDog',
			'types' => [$Dog, $Cat],
		]);

		$Intelligent = new InterfaceType([
			'name'   => 'Intelligent',
			'fields' => [
				'iq' => ['type' => Type::int()],
			],
		]);

		$Human = new ObjectType([
			'name'       => 'Human',
			'interfaces' => [$Being, $Intelligent],
			'fields'     => static function () use (&$Human, $Pet): array {
				assert($Human instanceof ObjectType);

				return [
					'name' => [
						'type' => Type::string(),
						'args' => ['surname' => ['type' => Type::boolean()]],
					],
					'pets'      => ['type' => Type::listOf($Pet)],
					'relatives' => ['type' => Type::listOf($Human)],
					'iq'        => ['type' => Type::int()],
				];
			},
		]);

		$Alien = new ObjectType([
			'name'       => 'Alien',
			'interfaces' => [$Being, $Intelligent],
			'fields'     => [
				'iq'   => ['type' => Type::int()],
				'name' => [
					'type' => Type::string(),
					'args' => ['surname' => ['type' => Type::boolean()]],
				],
				'numEyes' => ['type' => Type::int()],
			],
		]);

		$DogOrHuman = new UnionType([
			'name'  => 'DogOrHuman',
			'types' => [$Dog, $Human],
		]);

		$HumanOrAlien = new UnionType([
			'name'  => 'HumanOrAlien',
			'types' => [$Human, $Alien],
		]);

		$FurColor = new EnumType([
			'name'   => 'FurColor',
			'values' => [
				'BROWN'   => ['value' => 0],
				'BLACK'   => ['value' => 1],
				'TAN'     => ['value' => 2],
				'SPOTTED' => ['value' => 3],
				'NO_FUR'  => ['value' => null],
			],
		]);

		$ComplexInput = new InputObjectType([
			'name'   => 'ComplexInput',
			'fields' => [
				'requiredField'   => ['type' => Type::nonNull(Type::boolean())],
				'nonNullField'    => ['type' => Type::nonNull(Type::boolean()), 'defaultValue' => false],
				'intField'        => ['type' => Type::int()],
				'stringField'     => ['type' => Type::string()],
				'booleanField'    => ['type' => Type::boolean()],
				'stringListField' => ['type' => Type::listOf(Type::string())],
			],
		]);

		$ComplicatedArgs = new ObjectType([
			'name'   => 'ComplicatedArgs',
			'fields' => [
				'intArgField' => [
					'type' => Type::string(),
					'args' => ['intArg' => ['type' => Type::int()]],
				],
				'nonNullIntArgField' => [
					'type' => Type::string(),
					'args' => ['nonNullIntArg' => ['type' => Type::nonNull(Type::int())]],
				],
				'stringArgField' => [
					'type' => Type::string(),
					'args' => ['stringArg' => ['type' => Type::string()]],
				],
				'booleanArgField' => [
					'type' => Type::string(),
					'args' => ['booleanArg' => ['type' => Type::boolean()]],
				],
				'enumArgField' => [
					'type' => Type::string(),
					'args' => ['enumArg' => ['type' => $FurColor]],
				],
				'floatArgField' => [
					'type' => Type::string(),
					'args' => ['floatArg' => ['type' => Type::float()]],
				],
				'idArgField' => [
					'type' => Type::string(),
					'args' => ['idArg' => ['type' => Type::id()]],
				],
				'stringListArgField' => [
					'type' => Type::string(),
					'args' => ['stringListArg' => ['type' => Type::listOf(Type::string())]],
				],
				'stringListNonNullArgField' => [
					'type' => Type::string(),
					'args' => [
						'stringListNonNullArg' => [
							'type' => Type::listOf(Type::nonNull(Type::string())),
						],
					],
				],
				'complexArgField' => [
					'type' => Type::string(),
					'args' => ['complexArg' => ['type' => $ComplexInput]],
				],
				'multipleReqs' => [
					'type' => Type::string(),
					'args' => [
						'req1' => ['type' => Type::nonNull(Type::int())],
						'req2' => ['type' => Type::nonNull(Type::int())],
					],
				],
				'nonNullFieldWithDefault' => [
					'type' => Type::string(),
					'args' => [
						'arg' => ['type' => Type::nonNull(Type::int()), 'defaultValue' => 0],
					],
				],
				'multipleOpts' => [
					'type' => Type::string(),
					'args' => [
						'opt1' => [
							'type'         => Type::int(),
							'defaultValue' => 0,
						],
						'opt2' => [
							'type'         => Type::int(),
							'defaultValue' => 0,
						],
					],
				],
				'multipleOptAndReq' => [
					'type' => Type::string(),
					'args' => [
						'req1' => ['type' => Type::nonNull(Type::int())],
						'req2' => ['type' => Type::nonNull(Type::int())],
						'opt1' => [
							'type'         => Type::int(),
							'defaultValue' => 0,
						],
						'opt2' => [
							'type'         => Type::int(),
							'defaultValue' => 0,
						],
					],
				],
			],
		]);

		$invalidScalar = new CustomScalarType([
			'name'         => 'Invalid',
			'serialize'    => static fn ($value) => $value,
			'parseLiteral' => static function (Node $node): void {
				throw new UserError("Invalid scalar is always invalid: {$node->kind}");
			},
			'parseValue' => static function ($value): void {
				throw new UserError("Invalid scalar is always invalid: {$value}");
			},
		]);

		$anyScalar = new CustomScalarType([
			'name'         => 'Any',
			'serialize'    => static fn ($value) => $value,
			'parseValue'   => static fn ($value) => $value,
			'parseLiteral' => static fn ($node) => $node,
		]);

		$queryRoot = new ObjectType([
			'name'   => 'QueryRoot',
			'fields' => [
				'human' => [
					'args' => ['id' => ['type' => Type::id()]],
					'type' => $Human,
				],
				'alien'           => ['type' => $Alien],
				'dog'             => ['type' => $Dog],
				'cat'             => ['type' => $Cat],
				'pet'             => ['type' => $Pet],
				'catOrDog'        => ['type' => $CatOrDog],
				'dogOrHuman'      => ['type' => $DogOrHuman],
				'humanOrAlien'    => ['type' => $HumanOrAlien],
				'complicatedArgs' => ['type' => $ComplicatedArgs],
				'invalidArg'      => [
					'args' => [
						'arg' => ['type' => $invalidScalar],
					],
					'type' => Type::string(),
				],
				'anyArg' => [
					'args' => ['arg' => ['type' => $anyScalar]],
					'type' => Type::string(),
				],
			],
		]);

		$subscriptionRoot = new ObjectType([
			'name'   => 'SubscriptionRoot',
			'fields' => [
				'catSubscribe'  => ['type' => $Cat],
				'barkSubscribe' => ['type' => $Dog],
			],
		]);

		return new Schema([
			'query'        => $queryRoot,
			'subscription' => $subscriptionRoot,
			'directives'   => [
				Directive::includeDirective(),
				Directive::skipDirective(),
				Directive::deprecatedDirective(),
				new Directive([
					'name'      => 'directive',
					'locations' => [DirectiveLocation::FIELD, DirectiveLocation::FRAGMENT_DEFINITION],
				]),
				new Directive([
					'name'      => 'directiveA',
					'locations' => [DirectiveLocation::FIELD, DirectiveLocation::FRAGMENT_DEFINITION],
				]),
				new Directive([
					'name'      => 'directiveB',
					'locations' => [DirectiveLocation::FIELD, DirectiveLocation::FRAGMENT_DEFINITION],
				]),
				new Directive([
					'name'         => 'repeatable',
					'locations'    => [DirectiveLocation::FIELD, DirectiveLocation::FRAGMENT_DEFINITION],
					'isRepeatable' => true,
				]),
				new Directive([
					'name'      => 'onQuery',
					'locations' => [DirectiveLocation::QUERY],
				]),
				new Directive([
					'name'      => 'onMutation',
					'locations' => [DirectiveLocation::MUTATION],
				]),
				new Directive([
					'name'      => 'onSubscription',
					'locations' => [DirectiveLocation::SUBSCRIPTION],
				]),
				new Directive([
					'name'      => 'onField',
					'locations' => [DirectiveLocation::FIELD],
				]),
				new Directive([
					'name'      => 'onFragmentDefinition',
					'locations' => [DirectiveLocation::FRAGMENT_DEFINITION],
				]),
				new Directive([
					'name'      => 'onFragmentSpread',
					'locations' => [DirectiveLocation::FRAGMENT_SPREAD],
				]),
				new Directive([
					'name'      => 'onInlineFragment',
					'locations' => [DirectiveLocation::INLINE_FRAGMENT],
				]),
				new Directive([
					'name'      => 'onVariableDefinition',
					'locations' => [DirectiveLocation::VARIABLE_DEFINITION],
				]),
			],
		]);
	}

	/**
	 * @param array<string, mixed> $options
	 */
	protected function expectPassesRule(ValidationRule $rule, string $queryString, array $options = []): void
	{
		$this->expectValid(self::getTestSchema(), [$rule], $queryString, $options);
	}

	/**
	 * @param list<ValidationRule> $rules
	 * @param array<string, mixed> $options
	 */
	protected function expectValid(Schema $schema, array $rules, string $queryString, array $options = []): void
	{
		self::assertSame(
			[],
			DocumentValidator::validate($schema, Parser::parse($queryString, $options), $rules),
			'Should validate'
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $errors
	 * @param array<string, mixed>             $options
	 *
	 * @return array<int, Error>
	 */
	protected function expectFailsRule(
		ValidationRule $rule,
		string $queryString,
		array $errors,
		array $options = []
	): array {
		return $this->expectInvalid(self::getTestSchema(), [$rule], $queryString, $errors, $options);
	}

	/**
	 * @param list<ValidationRule>|null        $rules
	 * @param array<int, array<string, mixed>> $expectedErrors
	 * @param array<string, mixed>             $options
	 *
	 * @return array<int, Error>
	 */
	protected function expectInvalid(Schema $schema, ?array $rules, string $queryString, array $expectedErrors, array $options = []): array
	{
		$errors = DocumentValidator::validate($schema, Parser::parse($queryString, $options), $rules);

		self::assertNotEmpty($errors, 'GraphQL should not validate');
		self::assertEquals($expectedErrors, array_map([FormattedError::class, 'createFromException'], $errors));

		return $errors;
	}

	protected function expectPassesRuleWithSchema(Schema $schema, ValidationRule $rule, string $queryString): void
	{
		$this->expectValid($schema, [$rule], $queryString);
	}

	/**
	 * @param array<int, array<string, mixed>> $errors
	 */
	protected function expectFailsRuleWithSchema(
		Schema $schema,
		ValidationRule $rule,
		string $queryString,
		array $errors
	): void {
		$this->expectInvalid($schema, [$rule], $queryString, $errors);
	}

	protected function expectPassesCompleteValidation(string $queryString): void
	{
		$this->expectValid(self::getTestSchema(), array_values(DocumentValidator::allRules()), $queryString);
	}

	/**
	 * @param array<int, array<string, mixed>> $errors
	 */
	protected function expectFailsCompleteValidation(string $queryString, array $errors): void
	{
		$this->expectInvalid(self::getTestSchema(), array_values(DocumentValidator::allRules()), $queryString, $errors);
	}

	/**
	 * @param array<int, array<string, mixed>> $errors
	 */
	protected function expectSDLErrorsFromRule(
		ValidationRule $rule,
		string $sdlString,
		?Schema $schema = null,
		array $errors = []
	): void {
		$actualErrors = DocumentValidator::validateSDL(Parser::parse($sdlString), $schema, [$rule]);
		self::assertEquals(
			$errors,
			array_map([FormattedError::class, 'createFromException'], $actualErrors)
		);
	}

	protected function expectValidSDL(ValidationRule $rule, string $sdlString, ?Schema $schema = null): void
	{
		$this->expectSDLErrorsFromRule($rule, $sdlString, $schema, []);
	}

	protected static function createErrors(string $error, array $locations = []): array
	{
		$formatted = ['message' => $error];

		if ($locations !== []) {
			$formatted['locations'] = array_map(
				static fn (SourceLocation $loc): array => $loc->toArray(),
				$locations
			);
		}

		return $formatted;
	}

	private function getSchema(): Schema
	{
		$SomeBox = new InterfaceType([
			'name'   => 'SomeBox',
			'fields' => static function () use (&$SomeBox): array {
				return [
					'deepBox'        => ['type' => $SomeBox],
					'unrelatedField' => ['type' => Type::string()],
				];
			},
		]);

		$StringBox = new ObjectType([
			'name'       => 'StringBox',
			'interfaces' => [$SomeBox],
			'fields'     => static function () use (&$StringBox, &$IntBox): array {
				assert($StringBox instanceof ObjectType);

				return [
					'scalar'         => ['type' => Type::string()],
					'deepBox'        => ['type' => $StringBox],
					'unrelatedField' => ['type' => Type::string()],
					'listStringBox'  => ['type' => Type::listOf($StringBox)],
					'stringBox'      => ['type' => $StringBox],
					'intBox'         => ['type' => $IntBox],
				];
			},
		]);

		$IntBox = new ObjectType([
			'name'       => 'IntBox',
			'interfaces' => [$SomeBox],
			'fields'     => static function () use (&$StringBox, &$IntBox): array {
				return [
					'scalar'         => ['type' => Type::int()],
					'deepBox'        => ['type' => $IntBox],
					'unrelatedField' => ['type' => Type::string()],
					'listStringBox'  => ['type' => Type::listOf($StringBox)],
					'stringBox'      => ['type' => $StringBox],
					'intBox'         => ['type' => $IntBox],
				];
			},
		]);

		$NonNullStringBox1 = new InterfaceType([
			'name'   => 'NonNullStringBox1',
			'fields' => [
				'scalar' => ['type' => Type::nonNull(Type::string())],
			],
		]);

		$NonNullStringBox1Impl = new ObjectType([
			'name'       => 'NonNullStringBox1Impl',
			'interfaces' => [$SomeBox, $NonNullStringBox1],
			'fields'     => [
				'scalar'         => ['type' => Type::nonNull(Type::string())],
				'unrelatedField' => ['type' => Type::string()],
				'deepBox'        => ['type' => $SomeBox],
			],
		]);

		$NonNullStringBox2 = new InterfaceType([
			'name'   => 'NonNullStringBox2',
			'fields' => [
				'scalar' => ['type' => Type::nonNull(Type::string())],
			],
		]);

		$NonNullStringBox2Impl = new ObjectType([
			'name'       => 'NonNullStringBox2Impl',
			'interfaces' => [$SomeBox, $NonNullStringBox2],
			'fields'     => [
				'scalar'         => ['type' => Type::nonNull(Type::string())],
				'unrelatedField' => ['type' => Type::string()],
				'deepBox'        => ['type' => $SomeBox],
			],
		]);

		$Connection = new ObjectType([
			'name'   => 'Connection',
			'fields' => [
				'edges' => [
					'type' => Type::listOf(new ObjectType([
						'name'   => 'Edge',
						'fields' => [
							'node' => [
								'type' => new ObjectType([
									'name'   => 'Node',
									'fields' => [
										'id'   => ['type' => Type::id()],
										'name' => ['type' => Type::string()],
									],
								]),
							],
						],
					])),
				],
			],
		]);

		return new Schema([
			'query' => new ObjectType([
				'name'   => 'QueryRoot',
				'fields' => [
					'someBox'    => ['type' => $SomeBox],
					'connection' => ['type' => $Connection],
				],
			]),
			'types' => [$IntBox, $StringBox, $NonNullStringBox1Impl, $NonNullStringBox2Impl],
		]);
	}
}
