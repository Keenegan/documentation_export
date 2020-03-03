<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * DocumentationExport service.
 *
 * @package Drupal\documentation_export
 */
class DocumentationExport {

  use StringTranslationTrait;

  /**
   * The config factory used to retrieve the module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    FieldTypePluginManagerInterface $fieldTypePluginManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->configFactory = $configFactory->get('documentation_export.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypePluginManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Export the entities data from the configuration form.
   *
   * @return array
   *   The entities exported.
   */
  public function exportDocumentation() {
    // TODO Export accounts fields.
    $data = [];
    foreach ($this->configFactory->get('content_types') as $entity_type_id) {
      $storage = $this->getStorage($entity_type_id);
      if ($storage && $child_storage = $this->getStorage($storage->getEntityType()->getBundleOf())) {
        $bundle = $child_storage->getEntityType()->getBundleLabel();
        $data[$bundle] = $this->getDocumentationData($storage);
      }
    }
    return $data;
  }

  /**
   * Creates the documentation data array of an entity.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage object.
   *
   * @return array
   *   The entities data.
   */
  public function getDocumentationData(EntityStorageInterface $storage) {
    $data = [];
    foreach ($storage->loadMultiple() as $entity) {
      $data[$entity->label()]['entity'] = $entity;
      $fields = $this->entityFieldManager->getFieldDefinitions($storage->getEntityType()
        ->getBundleOf(), $entity->id());
      foreach ($fields as $field_definition) {
        /** @var \Drupal\field\Entity\FieldConfig $field_definition */
        if ($field_definition instanceof FieldConfig && !empty($field_definition->getTargetBundle())) {
          $field_type = $this->getFieldType($field_definition);

          // TODO use FieldConfigListBuilder to create the list ?
          $data[$entity->label()]['fields'][$field_type][$field_definition->getName()] = [
            'field_config' => $field_definition,
            'field_type' => $this->fieldTypeManager->getDefinitions()[$field_definition->getType()]['label'],
          ];
        }
      }
    }
    return $data;
  }

  /**
   * Returns the field type category of a field definition.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_definition
   *   The field definition object.
   *
   * @return string
   *   The field type.
   */
  private function getFieldType(FieldConfig $field_definition) {
    // TODO use CategorizingPluginManagerTrait instead of this ?
    $field_type = $field_definition->get('field_type');
    switch ($field_type) {
      case 'integer':
      case 'float':
      case 'decimal':
        $field_type = 'number';
        break;

      case 'text_with_summary':
      case 'string':
      case 'string_long':
      case 'text':
      case 'text_long':
        $field_type = 'text';
        break;

    }
    return $field_type;
  }

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type_id
   *   The entity type ID for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   A storage instance.
   */
  private function getStorage($entity_type_id) {
    try {
      return $this->entityTypeManager->getStorage($entity_type_id);
    }
    catch (\Exception $exception) {
      return NULL;
    }
  }

  /**
   * Returns an array of ConfigEntityType id that can be used by the module.
   *
   * @return array
   *   The entities supported by the module.
   */
  public function getSupportedOptions() {
    $result = [];
    $this->moduleHandler->moduleExists('node') ? $result['node_type'] = $this->t('Node') : NULL;
    $this->moduleHandler->moduleExists('taxonomy') ? $result['taxonomy_vocabulary'] = $this->t('Vocabulary') : NULL;
    $this->moduleHandler->moduleExists('media') ? $result['media_type'] = $this->t('Media') : NULL;
    $this->moduleHandler->moduleExists('paragraphs') ? $result['paragraphs_type'] = $this->t('Paragraph') : NULL;

    return $result;
  }

}
