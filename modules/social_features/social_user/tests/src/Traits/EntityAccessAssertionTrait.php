<?php

declare(strict_types=1);

namespace Drupal\Tests\social_user\Traits;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

trait EntityAccessAssertionTrait {

  /**
   * Assert that the entity access for the given operation is as expected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Access\AccessResult $expected
   */
  protected function assertEntityAccess(EntityInterface $entity, string $operation, AccountInterface $account, AccessResult $expected) : void {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $entity->access($operation, $account, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert that the field access for the given operation is as expected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @param string $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Access\AccessResult $expected
   */
  protected function assertFieldAccess(EntityInterface $entity, string $field_name, string $operation, AccountInterface $account, AccessResult $expected) : void {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $entity->get($field_name)->access($operation, $account, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert that the entity create access is as expected.
   *
   * @param string $entity_type
   * @param string|null $bundle
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param array $context
   * @param \Drupal\Core\Access\AccessResult $expected
   */
  protected function assertEntityCreateAccess(string $entity_type, ?string $bundle, AccountInterface $account, array $context, AccessResult $expected) : void {
    $result = $this->container->get('entity_type.manager')
      ->getAccessControlHandler($entity_type)
      ->createAccess($bundle, $account, $context, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert a certain set of result metadata on a query result.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $expected
   *   The expected metadata object.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $result
   *   The access result object.
   *
   * @internal
   */
  private function assertAccessMetadata(RefinableCacheableDependencyInterface $expected, RefinableCacheableDependencyInterface $result): void {
    static::assertEquals($expected->getCacheMaxAge(), $result->getCacheMaxAge(), 'Unexpected cache max age.');

    $missingContexts = array_diff($expected->getCacheContexts(), $result->getCacheContexts());
    static::assertEmpty($missingContexts, 'Missing cache contexts: ' . implode(', ', $missingContexts));

    $unexpectedContexts = array_diff($result->getCacheContexts(), $expected->getCacheContexts());
    static::assertEmpty($unexpectedContexts, 'Unexpected cache contexts: ' . implode(', ', $unexpectedContexts));

    $missingTags = array_diff($expected->getCacheTags(), $result->getCacheTags());
    static::assertEmpty($missingTags, 'Missing cache tags: ' . implode(', ', $missingTags));

    $unexpectedTags = array_diff($result->getCacheTags(), $expected->getCacheTags());
    static::assertEmpty($unexpectedTags, 'Unexpected cache tags: ' . implode(', ', $unexpectedTags));
  }

  /**
   * Asserts that two variables are equal.
   *
   * @throws ExpectationFailedException
   * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
   */
  abstract public static function assertEquals($expected, $actual, string $message = '', float $delta = 0.0, int $maxDepth = 10, bool $canonicalize = FALSE, bool $ignoreCase = FALSE): void;

  /**
   * Asserts that a variable is empty.
   *
   * @throws ExpectationFailedException
   * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
   */
  abstract public static function assertEmpty($actual, string $message = ''): void;

}
