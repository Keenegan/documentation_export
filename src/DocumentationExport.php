<?php

namespace Drupal\documentation_export;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class DocumentationExport implements DocumentationExportInterface {

  protected $configFactory;

  protected $entityTypeManager;

  protected $documentationData = [];

  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory->get('documentation_export.settings');
    $this->entityTypeManager = $entityTypeManager;
  }

  public function getConfiguration() {
  }

  public function exportDocumentation() {

    foreach ($this->configFactory->get('content_types') as $contentType) {
      $this->getDocumentationData($contentType);
    }
  }

  public function getDocumentationData($contentType) {
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($types as $type) {
      echo($type->get('name') . ' - ' . $type->get('description')) . '<br>';
      foreach ($this->entityTypeManager->getFieldDefinitions('node', $type->get('type')) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          echo $field_definition->getName() . ' ' . $field_definition->getDescription() . '<br>';
        }
      }
      echo '<br>';
      echo '<br>';
    }
  }

  public function fakeData() {
    return [
      [
        'first_name' => 'Test',
        'second_name' => 'Tata',
      ],
      [
        'first_name' => 'TOto',
        'second_name' => 'tutu',
      ],
    ];
  }

  public function printDocumenationAsCsv($data) {
    header("Content-Disposition: attachment; filename=\"test.csv\"");
    header("Content-Type: application/octet-stream");
    header("Connection: close");
    $fp = fopen("php://output", 'w');
    fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($fp, array_keys($data[0]));
    foreach ($data as $fields) {
      fputcsv($fp, $fields);
    }
    fclose($fp);
    die();
  }
}
