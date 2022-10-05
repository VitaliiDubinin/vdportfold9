<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "dxpr_builder_webform",
 *   category = @Translation("DXPR builder"),
 *   deriver = "Drupal\dxpr_builder\Plugin\Block\WebformBlockDeriver"
 * )
 */
class WebformBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $webform = Webform::load($this->getDerivativeId());
    if (!$webform || !$webform->access('submission_create', $account)) {
      return AccessResult::forbidden();
    }
    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (Webform::load($this->getDerivativeId())) {
      $build = [
        '#type' => 'webform',
        '#webform' => $this->getDerivativeId(),
        '#default_data' => [],
      ];
    }
    return $build;
  }

}
