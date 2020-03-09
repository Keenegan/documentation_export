<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exportDocumentation() {
    $definition = $this->entityTypeManager->getDefinition('field_config');
    $list_builder = $this->entityTypeManager->createHandlerInstance('Drupal\documentation_export\DocumentationListBuilder', $definition);

    $data = [];
    foreach ($this->configFactory->get('content_types') as $entity_type_id) {
      $storage = $this->getStorage($entity_type_id);
      // Bundlables entities.
      if ($storage && $this->getStorage($storage->getEntityType()->getBundleOf())) {
        foreach ($storage->loadMultiple() as $entity) {
          $data[$entity->getEntityType()->getLabel()->render()][] = $list_builder->render($storage->getEntityType()->getBundleOf(), $entity);
        }
      }
      // No bundlables entities (user).
      else {
        $data[$storage->getEntityType()->getLabel()->render()][] = $list_builder->render($entity_type_id, $storage->getEntityType());
      }
    }

    return $data;
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
    $this->moduleHandler->moduleExists('user') ? $result['user'] = $this->t('Users') : NULL;

    return $result;
  }

}
