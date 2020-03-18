<?php

namespace Drupal\documentation_export;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
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
   * The module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The module's configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    FieldTypePluginManagerInterface $field_type_manager,
    EntityFieldManagerInterface $entity_field_manager = NULL,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldTypeManager = $field_type_manager;
    if (!$entity_field_manager) {
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
    $this->config = $configFactory->get('documentation_export.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $this->targetEntityTypeId = $target_entity_type_id;
    $this->targetBundle = $target_bundle->id();

    $build = parent::render();
    $build['table']['#prefix'] = $this->createTablePrefix($target_bundle);
    $build['table']['#suffix'] = '<br>';
    $build['table']['#attributes']['id'] = 'field-overview';
    $build['table']['#empty'] = $this->t('No fields are present yet.');

    return $build;
  }

  /**
   * Creates the table prefix with entities data.
   *
   * @param $target_bundle
   *   The target bundle.
   *
   * @return string
   *   The table prefix.
   */
  public function createTablePrefix($target_bundle) {
    $description = '';
    if (is_a($target_bundle, 'Drupal\Core\Entity\ContentEntityType')) {
      $link = Link::fromTextAndUrl(
        $target_bundle->getLabel(), Url::fromRoute('entity.user.field_ui_fields')
      )->toString();
    }
    else {
      $link = Link::fromTextAndUrl(
        $target_bundle->label(), $target_bundle->toUrl()
      )->toString();
      if ($description = $target_bundle->getDescription()) {
        $description = $this->t('Description') . " : $description <br>";
      }
    }
    $header = "<h3>$link</h3>$description" . $this->t('Machine name') . ' : ' . $target_bundle->id() . '<br>';
    if ($this->config->get('show_entity_count') === 1) {
      $header .= $this->t('Number of entities on the site') . ' : ' . $this->getEntityCount($target_bundle) . '<br>';
    }
    return $header;
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
    $route_parameters = ['field_config' => $field_config->id()]
      + FieldUI::getRouteBundleParameter($this->entityTypeManager->getDefinition($this->targetEntityTypeId), $this->targetBundle);

    return [
      'id' => Html::getClass($field_config->getName()),
      'data' => [
        'label' => [
          'data' => [
            '#type' => 'link',
            '#title' => $field_config->getLabel(),
            '#url' => $field_config->toUrl("{$field_config->getTargetEntityTypeId()}-field-edit-form"),
            '#options' => ['attributes' => ['title' => $this->t('Edit field settings.')]],
          ],
        ],
        'field_name' => $field_config->getName(),
        'field_type' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
            '#url' => Url::fromRoute("entity.field_config.{$this->targetEntityTypeId}_storage_edit_form", $route_parameters),
            '#options' => ['attributes' => ['title' => $this->t('Edit field settings.')]],
          ],
        ],
        'field_description' => new FormattableMarkup($field_config->getDescription(), []),
        'field_cardinality' => $this->buildCardinality($field_config),
        'field_info' => $this->buildFieldInfo($field_config),
      ],
    ];
  }

  /**
   * Build the cardinality field.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *   The field config.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|int
   *   The cardinality value.
   */
  public function buildCardinality(FieldConfig $field_config) {
    if ($field_config->getFieldStorageDefinition()->getCardinality() === -1) {
      return $this->t('No limit');
    }
    return $field_config->getFieldStorageDefinition()->getCardinality();
  }

  /**
   * Build the information field.
   *
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *   The field config.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The formatted field data.
   */
  public function buildFieldInfo(FieldConfig $field_config) {
    $return = '';
    foreach ($field_config->getSettings() as $label => $value) {
      if ($value && is_string($value)) {
        $label = ucfirst(str_replace('_', ' ', $label));
        $return .= $this->t($label) . " : $value<br>";
      }
    }
    return new FormattableMarkup($return, []);
  }

  /**
   * Count how many entities exists on the site.
   *
   * @param $field_config
   *
   * @return array|int
   */
  public function getEntityCount($field_config) {
    if (is_a($field_config, 'Drupal\Core\Entity\ContentEntityType')) {
      return $this->entityTypeManager->getStorage($field_config->id())
        ->getQuery()
        ->count()
        ->execute();
    }
    else {
      //WIP
      $a = $field_config->getEntityType();
      $entity_type = $field_config->getEntityType()->getBundleOf();
      $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
      if ($a->getKey('id')) {
        $query->condition($a->getKey('id'), $field_config->id());
      }
      else {
        return null;
      }

      return $query->count()->execute();
    }
  }

}
