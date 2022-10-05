<?php

namespace Drupal\dxpr_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;

/**
 * Description.
 */
class PageController extends ControllerBase implements PageControllerInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The DXPR license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $license;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a PageController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $license
   *   The DXPR license service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    FormBuilderInterface $formBuilder,
    DxprBuilderLicenseServiceInterface $license,
    RequestStack $requestStack,
    Connection $connection
  ) {
    $this->formBuilder = $formBuilder;
    $this->license = $license;
    $this->requestStack = $requestStack;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('dxpr_builder.license_service'),
      $container->get('request_stack'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configPage() {
    return [
      '#prefix' => '<div id="dxpr_builder_configuration_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\dxpr_builder\Form\ConfigForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function pathsPage() {
    return [
      '#prefix' => '<div id="dxpr_builder_pathsPage_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\dxpr_builder\Form\PathsForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function userLicensesPage() {
    $rows = [];
    $users = $this->license->getLicenseUsers();
    foreach ($users as $mail => $info) {
      $uid = $this->connection->select('users_field_data', 'u')
        ->fields('u', ['uid'])
        ->condition('u.mail', $mail)
        ->execute()
        ->fetchField();
      $rows[] = [
        $uid ? [
          'data' => [
            '#type' => 'link',
            '#title' => $mail,
            '#url' => Url::fromRoute('entity.user.canonical', [
              'user' => $uid,
            ]),
          ],
        ] : $mail,
        [
          'data' => [
            '#theme' => 'item_list',
            '#items' => array_unique($info['roles']),
          ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->formatPlural(count($info['domains']), '1 site', '@count sites'),
            '#url' => Url::fromRoute('dxpr_builder.user_licenses.sites', [
              'mail' => $mail,
            ]),
            '#attributes' => [
              'class' => 'use-ajax',
              'data-dialog-type' => 'modal',
            ],
          ],
        ],
      ];
    }

    return [
      '#cache' => [
        'max-age' => 0,
      ],
      '#theme' => 'table',
      '#header' => [
        $this->t('User'),
        $this->t('Roles'),
        $this->t('Sites'),
      ],
      '#empty' => $this->t('There are no billable users recorded yet.'),
      '#rows' => $rows,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function userLicensesSitesPage() {
    $mail = $this->requestStack->getCurrentRequest()->get('mail');
    $users = $this->license->getLicenseUsers();
    $domains = isset($users[$mail]) ? $users[$mail]['domains'] : [];
    return [
      '#cache' => [
        '#max-age' => 0,
      ],
      '#theme' => 'item_list',
      '#items' => $domains,
    ];
  }

}
