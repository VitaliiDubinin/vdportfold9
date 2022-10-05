<?php

namespace Drupal\dxpr_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a dxpr builder profile entity type.
 */
interface DxprBuilderProfileInterface extends ConfigEntityInterface {

  /**
   * Loads the first profile available for specified roles.
   *
   * @return self
   *   return array of roles.
   */
  public static function loadByRoles(array $roles);

}
