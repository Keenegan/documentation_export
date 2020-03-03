<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\Entity\FieldConfig;

class DocumentationExport {

  protected $configFactory;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $fieldTypeManager;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    FieldTypePluginManagerInterface $fieldTypePluginManager
  ) {
    $this->configFactory = $configFactory->get('documentation_export.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypePluginManager;
  }

  public function exportDocumentation() {
    //TODO Accounts ?
    foreach ($this->configFactory->get('content_types') as $entity_type_id) {
      $storage = $this->getStorage($entity_type_id);
      if ($storage) {
        //TODO Find a way to convert node to Node or taxonomy_term to Taxonomy term.
        $data[$storage->getEntityType()->getBundleOf()] = $this->getDocumentationData($entity_type_id, $storage);
      }
    }
    return $data;
  }

  public function getDocumentationData($entity_type_id, $storage) {
    $data = [];
    foreach ($storage->loadMultiple() as $entity) {
      $data[$entity->label()]['entity'] = $entity;
      $fields = $this->entityFieldManager->getFieldDefinitions($storage->getEntityType()->getBundleOf(), $entity->id());
      foreach ($fields as $field_name => $field_definition) {
        /** @var \Drupal\field\Entity\FieldConfig $field_definition */
        if ($field_definition instanceof FieldConfig && !empty($field_definition->getTargetBundle())) {
          $field_type = $this->getFieldType($field_definition);

          //TODO use FieldConfigListBuilder to create the list ?
          $data[$entity->label()]['fields'][$field_type][$field_definition->getName()] = [
            'field_config' => $field_definition,
            'field_type' => $this->fieldTypeManager->getDefinitions()[$field_definition->getType()]['label']
          ];
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
