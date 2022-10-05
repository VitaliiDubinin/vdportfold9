<?php

namespace Drupal\dxpr_builder\Controller;

/**
 * Description.
 */
interface PageControllerInterface {

  /**
   * Page controller for the dxpr builder configuration page.
   *
   * @return array
   *   A render array representing the page, and containing
   *   the configuration form
   */
  public function configPage();

  /**
   * Page controller for the dxpr builder paths page.
   *
   * @return array
   *   A render array representing the page, and containing the paths form
   */
  public function pathsPage();

  /**
   * Page controller for the DXPR Builder user licenses page.
   *
   * @return array
   *   A render array representing the page.
   */
  public function userLicensesPage();

  /**
   * Page controller for the user licenses sites modal.
   *
   * @return array
   *   A render array representing the page.
   */
  public function userLicensesSitesPage();

}
