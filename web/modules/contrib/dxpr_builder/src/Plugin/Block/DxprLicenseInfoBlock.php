<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;

/**
 * Provides the license info block.
 *
 * This block shows the number of seats used and available.
 *
 * @Block(
 *   id = "license_info",
 *   category = @Translation("DXPR builder"),
 *   admin_label = @Translation("License info")
 * )
 */
class DxprLicenseInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * Block constructor.
   *
   * @param array $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definitions.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   The DXPR license service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DxprBuilderLicenseServiceInterface $license) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->license = $license;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dxpr_builder.license_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = $this->license->getLicenseInfo();
    if ($info) {
      return [
        '#cache' => [
          'max-age' => 0,
        ],
        '#theme' => 'dxpr-license-info',
        '#users_count' => $info['users_count'],
        '#seats_count' => min(intval($info['users_count']), intval($info['users_limit'])),
        '#seats_limit' => $info['users_limit'],
      ];
    }
    else {
      return [
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
