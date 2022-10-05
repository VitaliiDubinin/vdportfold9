<?php

namespace Drupal\dxpr_builder\Controller;

/**
 * Description.
 */
interface AjaxControllerInterface {

  /**
   * AJAX CSRF refresh: Refreshes csrf token on the fly.
   */
  public function ajaxRefresh();

  /**
   * Handles various operations for frontend drag and drop builder.
   */
  public function ajaxCallback();

  /**
   * Callback to handle AJAX file uploads.
   */
  public function fileUpload();

}
