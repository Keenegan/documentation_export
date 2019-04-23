<?php

namespace Drupal\documentation_export\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\documentation_export\DocumentationExportInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigurationForm extends ConfigFormBase {

  /** @var string Config settings */
  const SETTINGS = 'documentation_export.settings';

  protected $moduleHandler;

  protected $documentationExport;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, DocumentationExportInterface $documentationExport) {
    $this->moduleHandler = $moduleHandler;
    $this->documentationExport = $documentationExport;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('documentation_export_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'documentation_export_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getSupportedOptions(),
      '#title' => $this->t('Which entities should be exported.'),
      '#default_value' => $config->get('content_types'),
    ];

    $form['export'] = [
      '#type' => 'submit',
      '#value' => 'Export',
      '#submit' => [[$this, 'exportConfiguration']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public function getSupportedOptions() {
    $result = [
      'node' => $this->t('Node'),
      'taxonomy_vocabulary' => $this->t('Vocabulary'),
    ];

    $this->moduleHandler->moduleExists('paragraphs') ? $result['paragraphs_type'] = $this->t('Paragraph') : NULL;

    return $result;
  }

  public function exportConfiguration() {
    $this->documentationExport->exportDocumentation();
  }
}
