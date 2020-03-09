<?php

namespace Drupal\documentation_export\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\documentation_export\DocumentationExport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Dompdf\Dompdf;

/**
 * Class DocumentationExportController.
 *
 * @package Drupal\documentation_export\Controller
 */
class DocumentationExportController extends ControllerBase {

  /**
   * The documentationExport service.
   *
   * @var \Drupal\documentation_export\DocumentationExport
   */
  protected $documentationExport;

  /**
   * The Drupal renderer class.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $rendrer;

  /**
   * The Drupal configuration service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    DocumentationExport $documentationExport,
    RendererInterface $renderer,
    ConfigFactoryInterface $config
  ) {
    $this->documentationExport = $documentationExport;
    $this->rendrer = $renderer;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('documentation_export.service'),
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * Display the entities documentation list.
   *
   * @return array
   *   The render array used to display the list.
   */
  public function exportEntities() {
    return [
      'documentation_page' => [
        '#theme' => 'documentation_page',
        '#data' => $this->documentationExport->exportDocumentation(),
      ],
    ];
  }

  /**
   * Render the documentation page in a PDF file.
   *
   * @return array
   *   The render array for the controller.
   */
  public function printPdf() {
    $documentation_page = [
      'pdf_export_page' => [
        '#theme' => 'pdf_export_page',
        '#data' => $this->documentationExport->exportDocumentation(),
        '#stylesheet' => drupal_get_path('module', 'documentation_export') . '/styles.css',
      ],
    ];
    try {
      $dompdf = new Dompdf();
      $dompdf->loadHtml($this->rendrer->render($documentation_page));
      $dompdf->setPaper('A4', 'landscape');
      $dompdf->render();
      $dompdf->stream($this->config->get('system.site')->get('name') . ' entity documentation');
    }
    catch (\Exception $exception) {
      return [$exception->getMessage()];
    }
    return [];
  }

}
