<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Description.
 */
interface DxprBuilderLicenseServiceInterface {

  /**
   * The url of the domain status endpoint.
   */
  const DOMAIN_STATUS_ENDPOINT = 'https://dxpr.com/api/user-license';

  /**
   * The url of the domain blacklist endpoint.
   */
  const DOMAIN_BLACKLIST_ENDPOINT = 'https://www.dxpr.com/api/domain-blacklist/';

  /**
   * The url of endpoint to collect information about DXPR Builder users.
   */
  const CENTRAL_USER_STORAGE_ENDPOINT = 'https://dxpr.com/api/central-user-storage/';

  /**
   * The url of the site users endpoint.
   */
  const USERS_ENDPOINT = 'https://dxpr.com/api/user-license/users';

  /**
   * Operation name for syncing DXPR Builder users.
   */
  const DXPR_USER_SYNC_OPERATION = 'sync';

  /**
   * Operation name for adding new users to Central Storage.
   */
  const DXPR_USER_ADD_OPERATION = 'add';

  /**
   * Operation name for removing users from Central Storage.
   */
  const DXPR_USER_REMOVE_OPERATION = 'remove';

  /**
   * License check interval in seconds.
   */
  const LICENSE_CHECK_INTERVAL = 86400;

  /**
   * License check interval when currently not authorized in seconds.
   */
  const LICENSE_NOT_AUTHORIZED_INTERVAL = 1800;

  /**
   * Blacklist check interval in seconds.
   */
  const BLACKLIST_CHECK_INTERVAL = 3600;

  /**
   * Blacklist check interval when currently blacklisted in seconds.
   */
  const BLACKLIST_BLOCKED_INTERVAL = 60;

  /**
   * Array of the users tiers.
   */
  const TIERS_ARRAY = [1, 3, 5, 10, 15, 20, 30, 40, 50, 75, 100];

  /**
   * Retrieves license information.
   *
   * @return array|bool
   *   Array of license information or FALSE in case of fail.
   */
  public function getLicenseInfo();

  /**
   * Checks if the site is in the blacklist.
   *
   * @return bool
   *   Result of the checking.
   */
  public function isBlacklisted();

  /**
   * Check if the user given is allowed within the user limit.
   *
   * @return bool
   *   Indicates if access is allowed.
   */
  public function withinUsersLimit(AccountInterface $account);

  /**
   * Check if the entity given is allowed within the entity limit.
   *
   * @return bool
   *   Indicates if access is allowed.
   */
  public function withinEntitiesLimit(EntityInterface $entity);

  /**
   * Sends DXPR Users to central storage.
   *
   * @param \Drupal\user\UserInterface[] $users
   *   Users with a permission to edit with DXPR Builder.
   * @param string $operation
   *   Operation on user storage, add or remove.
   */
  public function syncUsersWithCentralStorage(array $users, string $operation);

  /**
   * Process a single item from the sync queue, if not empty.
   */
  public function processSyncQueue();

  /**
   * Collects User License information.
   *
   * @return array
   *   Information to be displayed on people page.
   */
  public function getUserLicenseInfo();

  /**
   * List users for license across all sites.
   *
   * @return array
   *   List of users.
   */
  public function getLicenseUsers();

}
