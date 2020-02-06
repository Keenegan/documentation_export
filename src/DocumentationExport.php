<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class DocumentationExport implements DocumentationExportInterface {

  protected $configFactory;

  protected $entityTypeManager;

  protected $entityFieldManager;

  protected $documentationData = [];

  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->configFactory = $configFactory->get('documentation_export.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  public function getConfiguration() {
  }

  public function exportDocumentation() {
    $test = ['node_type'];
    //foreach ($this->configFactory->get('content_types') as $contentType) {
    foreach ($test as $contentType) {
      $this->getDocumentationData($contentType);
    }
  }

  public function getDocumentationData($contentType) {
    $data = [];
    try {
      $types = $this->entityTypeManager->getStorage($contentType)
        ->loadMultiple();
    }
    catch (\Exception $exception) {
      return NULL;
    }
    foreach ($types as $type) {
      $data[$type->bundle()] = [
        'name' => $type->get('name'),
        'description' => $type->get('description'),
      ];
      //echo ($type->get('name') . ' - ' . $type->get('description')) . '<br>';
      foreach ($this->entityFieldManager->getFieldDefinitions('node', $type->id()) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          $data[$type->bundle()]['fields'][$field_definition->getName()] = $field_definition->getDescription();
        }
      }
    }
    return $data;
  }



}
