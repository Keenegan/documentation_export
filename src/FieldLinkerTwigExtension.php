<?php

namespace Drupal\documentation_export;

use Drupal\Core\Link;
use Drupal\field\Entity\FieldConfig;

/**
 * Describe the twig extensions created by this module.
 *
 * @package Drupal\documentation_export
 */
class FieldLinkerTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'documentation_export.field_linker';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('field_linker', [$this, 'fieldLinker']),
      new \Twig_SimpleFilter('entity_linker', [$this, 'entityLinker']),
    ];
  }

  /**
   * Convert a FieldConfig entity into an url to the field.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *   The field config entity.
   *
   * @return \Drupal\Core\Link|mixed|string|null
   *   The link, or just the label if there was an error.
   */
  public function fieldLinker(FieldConfig $field_config) {
    try {
      $url = $field_config->toUrl("{$field_config->getTargetEntityTypeId()}-field-edit-form");
      return Link::fromTextAndUrl($field_config->label(), $url);
    }
    catch (\Exception $exception) {
      return $field_config->label();
    }
  }

  public function entityLinker($entity) {
    try {
      $url = $entity->toUrl();
      return Link::fromTextAndUrl($entity->label(), $url);
    }
    catch (\Exception $exception) {
      return $entity->label();
    }
  }

}
