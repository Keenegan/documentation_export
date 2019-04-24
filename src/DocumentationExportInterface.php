<?php

namespace Drupal\documentation_export;

interface DocumentationExportInterface {

  public function getConfiguration();

  public function exportDocumentation();

}
