<?php

namespace Drupal\dxpr_builder\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to indicate if the user is a DXPR Builder user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dxpr_builder_user_field")
 */
class DxprBuilderUserField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query needed for this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getEntity($values);
    if ($user->hasPermission('edit with dxpr builder')) {
      return ['#markup' => $this->t('Yes')];
    }
    return ['#markup' => $this->t('No')];
  }

}
