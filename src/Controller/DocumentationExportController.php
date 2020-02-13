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
    return $build['dblog_table'] = [
      '#theme' => 'documentation_export',
      '#data' => $this->documentationExport->exportDocumentation(),
    ];
  }

}
