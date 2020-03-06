<?php

namespace Drupal\documentation_export;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field config entities.
 */
class DocumentationListBuilder extends EntityListBuilder {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = [
    'entityManager' => 'entity.manager',
  ];

  /**
   * The name of the entity type the listed fields are attached to.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * The name of the bundle the listed fields are attached to.
   *
   * @var string
   */
  protected $targetBundle;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, FieldTypePluginManagerInterface $field_type_manager, EntityFieldManagerInterface $entity_field_manager = NULL) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
    if (!$entity_field_manager) {
      @trigger_error('Calling FieldConfigListBuilder::__construct() with the $entity_field_manager argument is supported in Drupal 8.7.0 and will be required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $this->targetEntityTypeId = $target_entity_type_id;
    $this->targetBundle = $target_bundle;

    $build = parent::render();
    $build['table']['#prefix'] = "$target_entity_type_id - $target_bundle";
    $build['table']['#attributes']['id'] = 'field-overview';
    $build['table']['#empty'] = $this->t('No fields are present yet.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array_filter($this->entityFieldManager->getFieldDefinitions($this->targetEntityTypeId, $this->targetBundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Label'),
      'field_name' => [
        'data' => $this->t('Machine name'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'field_type' => $this->t('Field type'),
      'field_desription' => $this->t('Description'),
      'field_cardinality' => $this->t('Cardinality'),
      'field_info' => $this->t('Field info'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_storage = $field_config->getFieldStorageDefinition();
    $route_parameters = [
        'field_config' => $field_config->id(),
      ] + FieldUI::getRouteBundleParameter($this->entityTypeManager->getDefinition($this->targetEntityTypeId), $this->targetBundle);

    $row = [
      'id' => Html::getClass($field_config->getName()),
      'data' => [
        'label' => $field_config->getLabel(),
        'field_name' => $field_config->getName(),
        'field_type' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
            '#url' => Url::fromRoute("entity.field_config.{$this->targetEntityTypeId}_storage_edit_form", $route_parameters),
            '#options' => ['attributes' => ['title' => $this->t('Edit field settings.')]],
          ],
        ],
        'field_description' => $field_config->getDescription(),
        'field_cardinality' => $field_config->getFieldStorageDefinition()
          ->getCardinality(),
        'field_info' => $this->getInfo($field_config),
      ],
    ];

    // Add the operations.
    $row['data'] = $row['data'] + parent::buildRow($field_config);

    if ($field_storage->isLocked()) {
      //$row['data']['operations'] = ['data' => ['#markup' => $this->t('Locked')]];
      $row['class'][] = 'menu-disabled';
    }

    return $row;
  }

  public function getInfo($field_config) {
    $return = '';
    foreach ($field_config->getSettings() as $label => $value) {
      $return .= "$label : $value". "<br>";
    }
    return $return;
  }


}

