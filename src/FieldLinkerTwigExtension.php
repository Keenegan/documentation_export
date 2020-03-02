<?php

namespace Drupal\documentation_export;


use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;

/**
 * extend Drupal's Twig_Extension class
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
      new \Twig_SimpleFilter('field_linker', [$this, 'field_linker'])
    ];
  }

  public function field_linker(FieldConfig $field_config) {
    try {
      $url = $field_config->toUrl("{$field_config->getTargetEntityTypeId()}-field-edit-form");
      return Link::fromTextAndUrl($field_config->label(), $url);
    }
    catch (\Exception $exception) {
      return $field_config->label();
    }

  }

}
