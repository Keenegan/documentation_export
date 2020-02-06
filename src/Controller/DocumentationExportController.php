<?php

namespace Drupal\documentation_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\documentation_export\DocumentationExportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DocumentationExportController
 *
 * @package Drupal\documentation_export\Controller
 */
class DocumentationExportController extends ControllerBase {

  protected $documentationExport;

  public function __construct(DocumentationExportInterface $documentationExport) {
    $this->documentationExport = $documentationExport;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('documentation_export.service')
    );
  }

  public function exportToHtml() {
    $data = $this->documentationExport->exportDocumentation();
    $rows = [
      [
        ['data' => $this->t('Node'), 'header' => TRUE],
      ],
      [
        ['data' => $this->t('Paragraphs'), 'header' => TRUE],
      ],
    ];
    return $build['dblog_table'] = [
      '#type' => 'table',
      '#rows' => $data,
    ];
  }

}
