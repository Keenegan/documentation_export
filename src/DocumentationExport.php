<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;

class DocumentationExport {

  protected $configFactory;

  protected $entityTypeManager;

  protected $entityFieldManager;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->configFactory = $configFactory->get('documentation_export.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  public function exportDocumentation() {
    foreach (['node_type', 'paragraphs_type'] as $entity_type_id) {
      $data[$entity_type_id] = $this->getDocumentationData($entity_type_id);
    }
    return $data;
  }

  public function getDocumentationData($entity_type_id) {
    //@TODO reduce this ?
    $storage = $this->getStorage($entity_type_id);
    if ($storage === NULL) {
      return NULL;
    }

    $data = [];
    foreach ($storage->loadMultiple() as $entity) {
      $data[$entity->id()] = $entity->toArray();
      foreach ($this->entityFieldManager->getFieldDefinitions($storage->getEntityType()->getBundleOf(), $entity->id()) as $field_name => $field_definition) {
        /** @var \Drupal\field\Entity\FieldConfig $field_definition */
        if ($field_definition instanceof FieldConfig && !empty($field_definition->getTargetBundle())) {
          $field_type = $this->getFieldType($field_definition);
          $data[$entity->id()]['fields'][$field_type][$field_definition->getName()] = $field_definition->toArray();
        }
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\field\Entity\FieldConfig $field_definition
   *
   * @return string
   */
  private function getFieldType($field_definition) {
    //@TODO use CategorizingPluginManagerTrait instead of this ?
    $field_type = $field_definition->get('field_type');
    switch ($field_type) {
      case 'integer':
      case 'float':
      case 'decimal':
        return 'number';
        break;
      case 'text_with_summary':
      case 'string':
      case 'string_long':
      case 'text':
      case 'text_long':
        return 'text';
        break;
      default:
        return $field_type;
    }
  }

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   */
  private function getStorage($entity_type_id) {
    try {
      return $this->entityTypeManager->getStorage($entity_type_id);
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

}
