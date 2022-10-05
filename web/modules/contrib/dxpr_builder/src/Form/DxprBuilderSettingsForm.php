<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder;

/**
 * Provides a form for DXPR Builder Settings.
 */
class DxprBuilderSettingsForm extends FormBase {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * JWT service to manipulate the DXPR JSON token.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder
   */
  protected $jwtDecoder;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder $jwtDecoder
   *   Parsing DXPR JWT token.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
    DxprBuilderJWTDecoder $jwtDecoder
  ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->jwtDecoder = $jwtDecoder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('dxpr_builder.jwt_decoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dxpr_builder_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Disable HTML5 validation for this form. This gives UX issues for
    // required fields inside fieldsets, while Drupal's own validation
    // works fine for these cases.
    $form['#attributes']['novalidate'] = TRUE;

    // $form = parent::buildForm($form, $form_state);
    $config = $this->configFactory->get('dxpr_builder.settings');

    $form['license_info'] = [
      '#type' => 'details',
      '#title' => $this->t('DXPR License'),
      '#description' => $this->t('Please enter your license key. You can find your license key (JSON Web Token) at the bottom of your <a href=":uri" target="_blank">subscription overview page</a>', [
        ':uri' => 'https://app.dxpr.com/user/me/subscription',
      ]),
    ];

    $form['license_info']['json_web_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON Web Token'),
      '#default_value' => $config->get('json_web_token'),
      '#required' => TRUE,
    ];

    $form['custom_overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('DXPR Builder Overrides'),
      '#description' => $this->t('Customize DXPR Builder  behaviors.'),
    ];

    $form['custom_overrides']['offset_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smooth-scroll offset selector'),
      '#description' => $this->t('When using a #hash to link to content inside a DXPR Builder container, the user will smooth-scroll to this content. DXPR Builder will also open any tabs, collapsibles, or carousel slides that contain this content. DXPR Builder automatically corrects the amount of page it scrolls to account for the DXPR Theme fixed or sticky header. If you use a different theme you can use this setting to correct the smooth-scroll behavior for your fixed header by adding a CSS selector here.'),
      '#default_value' => $config->get('offset_selector', '.dxpr-theme-header--sticky, .dxpr-theme-header--fixed'),
    ];

    $form['ui_customization'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Editor Overrides'),
      '#description' => $this->t('Extend DXPR Builder text editor style options and text editor font options.'),
    ];

    $form['ui_customization']['cke_stylesset'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Editor Formatting Styles'),
      '#description' => $this->t('Enter one class on each line in the format: @format. Example: @example.', [
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-stylesSet',
        '@format' => '[label]=[element].[class]',
        '@example' => 'Sassy Title=h1.sassy-title',
      ]) . ' ' . $this->t('Uses the <a href="@url">@setting</a> setting internally.', [
        '@setting' => 'stylesSet',
        '@url' => 'http://docs.ckeditor.com/#!/api/CKEDITOR.config-cfg-stylesSet',
      ]
      ),
      '#default_value' => $config->get('cke_stylesset', ''),
      // '#element_validate' => array('form_validate_stylesset'),
    ];

    $form['ui_customization']['cke_fonts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text Editor Font Options'),
      '#default_value' => $config->get('cke_fonts', ''),
      '#description' => $this->t('Enter one class on each line in the format: @format. Example: @example<br />The font selector is only available if you add it in a DXPR Builder profile.', [
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-font_names',
        '@format' => 'Font Label/Font Name, Alternative System Font',
        '@example' => 'Times New Roman/Times New Roman, Times, serif',
      ]) . ' ' . $this->t('Uses the <a href="@url">@setting</a> setting internally.', [
        '@setting' => 'font_names',
        '@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-font_names',
      ]),
    ];

    $form['bootstrap_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Bootstrap Assets'),
    ];

    $form['bootstrap_details']['bootstrap'] = [
      '#type' => 'radios',
      '#title' => $this->t('Include Bootstrap Files'),
      '#description' => $this->t("Bootstrap 3 is required. Bootstrap 3 Light is recommended if your theme has conflicts with Bootstrap 3 CSS. Bootstrap Light includes all grid and helper classes but doesn't contain normalize.css and some typography styles."),
      '#options' => [
        0 => $this->t('No'),
        2 => $this->t('Load Bootstrap 3 Light'),
        1 => $this->t('Load Bootstrap 3 Full'),
      ],
      '#default_value' => $config->get('bootstrap', 0),
    ];

    $form['media_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Media Browser'),
    ];
    $default = ['' => $this->t('None (Use basic file upload widget)')];
    if ($this->moduleHandler->moduleExists('entity_browser')) {
      /** @var array $media_browsers */
      $media_browsers = $this->entityTypeManager->getStorage('entity_browser')->getQuery()->execute();
      $media_browsers = $default + $media_browsers;
    }
    else {
      $media_browsers = $default;
    }
    $form['media_details']['media_browser'] = [
      '#type' => 'radios',
      '#title' => $this->t('Media Browser'),
      '#description' => $this->t('DXPR Builder supports media image reusability via the Entity Browser module. The Entity Browser selected here will be used by the editor. The Entity Browser has to be using the iFrame display plugin.'),
      '#options' => $media_browsers,
      '#default_value' => $config->get('media_browser', FALSE),
    ];

    $form['experimental'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Format Filters'),
    ];

    $form['experimental']['format_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process Text Format Filters on Frontend Builder content'),
      '#description' => $this->t("Use with caution. If a field uses DXPR Builder as field formatter any filters that are set on the field's text format will be ignored. This is because when editing on the frontend, you are editing the raw field contents. With this setting enabled the DXPR editor still loads raw fields content, but users that don't have DXPR Builder editing permission will get a filtered field. Some filters will not work at all with DXPR Builder while others should work just fine."),
      '#default_value' => $config->get('format_filters', FALSE),
    ];

    // If a local build exists, provide the option to use it instead of the
    // cloud-hosted files.
    $frontend_asset_options = [
      0 => $this->t('Cloud'),
    ];
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.min.js')) {
      $frontend_asset_options[1] = $this->t('Local files (minified)');
    }
    if (file_exists(__DIR__ . '/../../dxpr_builder/dxpr_builder.js')) {
      $frontend_asset_options[2] = $this->t('Local files (unminified)');
    }
    if (count($frontend_asset_options) > 1) {
      $form['editor_assets'] = [
        '#type' => 'details',
        '#title' => $this->t('Editor Assets'),
      ];
      $form['editor_assets']['editor_assets_source'] = [
        '#type' => 'radios',
        '#title' => $this->t('Source for Editor Assets'),
        '#description' => $this->t("Assets may be loaded from the cloud to provide an up-to-date version (recommended), or can be loaded from a local build."),
        '#options' => $frontend_asset_options,
        '#default_value' => $config->get('editor_assets_source', 0),
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $jwtPayloadData = $this->jwtDecoder->decodeJwt($form_state->getValue('json_web_token'));
    if ($jwtPayloadData['sub'] === NULL || $jwtPayloadData['scope'] === NULL) {
      $form_state->setErrorByName('json_web_token', $this->t('Your DXPR Builder product key can’t be read, please make sure you copy the whole key without any trailing or leading spaces into the form.'));
      Cache::invalidateTags(['config:dxpr_builder.settings']);
    }
    elseif ($jwtPayloadData['dxpr_tier'] === NULL) {
      $form_state->setErrorByName('json_web_token', $this->t('Your product key (JWT) is outdated and not compatible with DXPR Builder version 2.0.0 and up. Please follow instructions <a href=":uri">here</a> to get a new product key.', [
        ':uri' => 'https://app.dxpr.com/download/all#token',
      ]));
      Cache::invalidateTags(['config:dxpr_builder.settings']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('dxpr_builder.settings');

    $old_editor_assets_source = $config->get('editor_assets_source', 0);
    $old_json_web_token = $config->get('json_web_token');

    $config
      ->set('bootstrap', $form_state->getValue('bootstrap'))
      ->set('cke_fonts', $form_state->getValue('cke_fonts'))
      ->set('cke_stylesset', $form_state->getValue('cke_stylesset'))
      ->set('editor_assets_source', $form_state->getValue('editor_assets_source'))
      ->set('format_filters', $form_state->getValue('format_filters'))
      ->set('media_browser', $form_state->getValue('media_browser'))
      ->set('offset_selector', $form_state->getValue('offset_selector'))
      ->set('json_web_token', $form_state->getValue('json_web_token'))
      ->save();

    $this->messenger()->addStatus($this->t('The configuration has been updated'));
    Cache::invalidateTags(['config:dxpr_builder.settings']);

    // Invalidate caches for the library declarations provided by
    // dxpr_builder_library_info_build().
    // There is no cache tag for this part.
    if ($old_editor_assets_source != $config->get('editor_assets_source', 0) || $old_json_web_token != $config->get('json_web_token')) {
      drupal_flush_all_caches();
    }
  }

}
