<?php

namespace Drupal\dxpr_builder\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\dxpr_builder\Entity\DxprBuilderProfile;
use Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface;
use Drupal\dxpr_builder\Service\DxprBuilderServiceInterface;
use Drupal\dxpr_builder\Service\Handler\ProfileHandler;
use Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'dxpr_builder_text' formatter.
 *
 * @FieldFormatter(
 *    id = "dxpr_builder_text",
 *    label = @Translation("DXPR Builder"),
 *    field_types = {
 *       "text",
 *       "text_long",
 *       "text_with_summary"
 *    }
 * )
 */
class DxprBuilderFormatter extends FormatterBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The CSRF token generator service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The dxpr builder service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface
   */
  protected $dxprBuilderService;

  /**
   * The dxpr builder license service.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  private $dxprBuilderLicenseService;

  /**
   * The profile handler service.
   *
   * @var \Drupal\dxpr_builder\Service\Handler\ProfileHandler
   */
  private $profileHandler;

  /**
   * Parsing yaml file.
   *
   * @var \Drupal\Core\Extension\InfoParser
   */
  private $infoParser;

  /**
   * JWT service to manipulate the DXPR JSON token.
   *
   * @var \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder
   */
  protected $jwtDecoder;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Construct a DxprBuilderFormatter object.
   *
   * @param string $plugin_id
   *   The ID of the formatter.
   * @param string $plugin_definition
   *   The formatter definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field.
   * @param array $settings
   *   The settings of the formatter.
   * @param string $label
   *   The position of the lable when the field is rendered.
   * @param string $view_mode
   *   The current view mode.
   * @param array $third_party_settings
   *   Any third-party settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderServiceInterface $dxprBuilderService
   *   The dxpr builder service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface $dxpr_builder_license_service
   *   The dxpr builder license service.
   * @param \Drupal\dxpr_builder\Service\Handler\ProfileHandler $profile_handler
   *   The profile handler service.
   * @param \Drupal\Core\Extension\InfoParser $infoParser
   *   Parsing yaml file service.
   * @param \Drupal\dxpr_builder\Service\DxprBuilderJWTDecoder $jwtDecoder
   *   Parsing DXPR JWT token.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    CurrentPathStack $currentPathStack,
    RequestStack $requestStack,
    LanguageManagerInterface $languageManager,
    CsrfTokenGenerator $csrfToken,
    ExtensionPathResolver $extensionPathResolver,
    ModuleHandlerInterface $moduleHandler,
    RendererInterface $renderer,
    DxprBuilderServiceInterface $dxprBuilderService,
    DxprBuilderLicenseServiceInterface $dxpr_builder_license_service,
    ProfileHandler $profile_handler,
    InfoParser $infoParser,
    DxprBuilderJWTDecoder $jwtDecoder,
    ThemeHandlerInterface $theme_handler,
    ThemeManagerInterface $theme_manager,
    Messenger $messenger
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
    $this->currentPathStack = $currentPathStack;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
    $this->csrfToken = $csrfToken;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->dxprBuilderService = $dxprBuilderService;
    $this->dxprBuilderLicenseService = $dxpr_builder_license_service;
    $this->profileHandler = $profile_handler;
    $this->infoParser = $infoParser;
    $this->jwtDecoder = $jwtDecoder;
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
    $this->messenger = $messenger;
  }

  /**
   * Description.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('csrf_token'),
      $container->get('extension.path.resolver'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('dxpr_builder.service'),
      $container->get('dxpr_builder.license_service'),
      $container->get('dxpr_builder.profile_handler'),
      $container->get('info_parser'),
      $container->get('dxpr_builder.jwt_decoder'),
      $container->get('theme_handler'),
      $container->get('theme.manager'),
      $container->get('messenger')
    );
  }

  /**
   * Description.
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('The DXPR Builder drag and drop interface');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $config = $this->configFactory->get('dxpr_builder.settings');

    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();
    $id = $entity->id();
    $vid = $entity->getRevisionId();
    $field_name = $this->fieldDefinition->getName();
    $container_name = $id . '|' . $vid . '|' . $field_name;
    $entity_label = $entity->label();
    $loadAsBlock = FALSE;
    if ($this->requestStack->getCurrentRequest()->getPathInfo() == '/dxpr_builder/ajax') {
      $loadAsBlock = TRUE;
    }
    $enable_editor = FALSE;
    $has_permission = $this->currentUser->hasPermission('edit with dxpr builder')
      && $entity->access('update', $this->currentUser)
      && !$loadAsBlock
      && !$this->dxprBuilderLicenseService->isBlacklisted();
    $warning = NULL;
    if ($has_permission) {
      $within_users_limit = $this->dxprBuilderLicenseService->withinUsersLimit($this->currentUser);
      $within_entities_limit = $this->dxprBuilderLicenseService->withinEntitiesLimit($entity);
      $enable_editor = $within_users_limit && $within_entities_limit;
      if (!$within_users_limit) {
        $warning = $this->t('Your access to DXPR Builder is restricted, please add more User licenses to your DXPR.com account to restore access.');
      }
      if (!$within_entities_limit) {
        $warning = $this->t('You cannot author more than 20 content items with a free account. Please add a paid subscription at DXPR.com to create more content with DXPR Builder.');
      }
      if ($warning) {
        $this->messenger->addMessage($warning, 'warning');
      }
    }

    foreach ($items as $delta => $item) {
      // Ignore initializing the builder at excluded pages and empty entities.
      if ($item->getEntity()->id() == NULL) {
        continue;
      }
      $value = $item->value;
      $element[$delta] = [];
      if ($item->getLangcode()) {
        $langcode = $item->getLangcode();
      }
      else {
        $langcode = $this->languageManager->getCurrentLanguage()->getId();
      }
      $human_readable = base64_encode(Html::escape($field_name . ' on ' . str_replace('node', 'page', $entity_type) . ' ' . $entity_label . ' '));
      $attrs = 'class="az-element az-container dxpr" data-az-type="' . $entity_type . '|' . $bundle . '" data-az-name="' . $container_name . '" data-az-human-readable="' . $human_readable . '" data-az-langcode="' . $langcode . '"';
      preg_match('/^\s*\<[\s\S]*\>\s*$/', $value, $html_format);

      if (empty($html_format) && $enable_editor) {
        $output = '<div ' . $attrs . ' style="display:none"></div>';
        $mode = 'static';
      }
      else {
        $response = $this->dxprBuilderService->updateHtml($value, $enable_editor);
        $output = $response['output'];
        $mode = $response['mode'];
        $libraries = $response['library'];
        $settings = $response['settings'];

        foreach ($libraries as $library) {
          $element[$delta]['#attached']['library'][] = $library;
        }

        // Adds html_head scripts.
        if (isset($settings['dxpr_html_head'])) {
          $element[$delta]['#attached']['html_head'] = $settings['dxpr_html_head'];
          unset($settings['dxpr_html_head']);
        }

        foreach ($settings as $key => $setting) {
          $element[$delta]['#attached']['drupalSettings'][$key] = $setting;
        }
        $output = '<div ' . $attrs . ' data-az-mode="' . $mode . '">' . $output . '</div>';

        // DXPR Builder 1.1.0 Experimental feature: Process Text Format
        // Filters for non-editors ~Jur 15/06/16
        // Don't run text format filters when editor is loaded because
        // the editor would save all filter output into the db.
        if (!$this->currentUser->hasPermission('edit with dxpr builder') && $config->get('format_filters', FALSE)) {
          $build = [
            '#type' => 'processed_text',
            '#text' => $output,
            '#format' => $item->__get('format'),
            '#  ' => [],
            '#langcode' => $langcode,
          ];

          $output = $this->renderer->renderPlain($build);
        }
      }

      $element[$delta]['#markup'] = Markup::create($output);
      $element[$delta]['#id'] = $id . '|' . $field_name;
      // Attach DXPR Builder assets.
      $this->attachAssets($container_name, $element[$delta], $value, $html_format, $enable_editor, $mode, $this->languageManager->getCurrentLanguage()->getId());
    }

    $element['#cache']['max-age'] = $warning ? 0 : DxprBuilderLicenseServiceInterface::LICENSE_NOT_AUTHORIZED_INTERVAL;
    $element['#cache']['contexts'] = ['url'];
    $element['#cache']['tags'] = $config->getCacheTags();
    $profile = DxprBuilderProfile::loadByRoles($this->currentUser->getRoles());
    if ($profile) {
      $profile_settings = $this->profileHandler->buildSettings($profile);
      $element['#attached']['drupalSettings']['dxprBuilder']['profile'] = $profile_settings;
      $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], $profile->getCacheTags());
    }

    $this->moduleHandler->invokeAll('dxpr_builder_view_elements', [&$element]);

    return $element;
  }

  /**
   * Attaches CSS and JS assets to field render array.
   *
   * @param string $container_name
   *   Unique container identifier.
   * @param array $element
   *   A renderable array for the $items, as an array of child
   *   elements keyed by numeric indexes starting from 0.
   * @param string $content
   *   Raw field value.
   * @param string $html_format
   *   Valid HTML field value.
   * @param bool $enable_editor
   *   When FALSE only frontend rendering assets will be attached. When TRUE
   *   the full drag and drop editor will be attached.
   * @param string $mode
   *   The mode.
   * @param string $dxpr_lang
   *   Two letter language code.
   *
   * @see https://api.drupal.org/api/drupal/modules!field!field.api.php/function/hook_field_formatter_view/7.x
   */
  public function attachAssets($container_name, array &$element, $content, $html_format, $enable_editor, $mode, $dxpr_lang) {
    $config = $this->configFactory->get('dxpr_builder.settings');

    $settings = [];
    $settings['disallowContainers'] = [];
    $settings['currentPath'] = $this->currentPathStack->getPath();

    $offset_selector = $config->get('offset_selector', '.dxpr-theme-header--sticky, .dxpr-theme-header--fixed');

    if ($offset_selector) {
      $settings['offsetSelector'] = $offset_selector;
    }

    if ($enable_editor) {
      $settings['dxprEditor'] = TRUE;
    }

    if ($this->moduleHandler->moduleExists('dxpr_builder_e')) {
      $settings['enterprise'] = TRUE;
    }

    $url = Url::fromRoute('dxpr_builder.ajax_callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $dxprBuilderPath = $this->getPath('module', 'dxpr_builder');
    $url->setOptions(['absolute' => TRUE, 'query' => ['token' => $token]]);
    $settings['dxprAjaxUrl'] = $url->toSTring();

    $csrf_url = Url::fromRoute('dxpr_builder.csrf_refresh');
    $csrf_url->setOptions(['absolute' => TRUE]);
    $settings['dxprCsrfUrl'] = $csrf_url->toSTring();

    $settings['dxprLanguage'] = $dxpr_lang;

    $infoFile = $this->infoParser->parse($dxprBuilderPath . '/dxpr_builder.info.yml');
    if (!empty($infoFile['version'])) {
      $settings['dxprBuilderVersion'] = $infoFile['version'];
    }
    else {
      $settings['dxprBuilderVersion'] = 'dev';
    }

    $settings['dxprBaseUrl'] = base_path() . $dxprBuilderPath . '/dxpr_builder/';
    $settings['dxprBasePath'] = base_path();

    if ($config->get('json_web_token') != NULL) {
      $jwtPayloadData = $this->jwtDecoder->decodeJwt($config->get('json_web_token'));
      if ($jwtPayloadData['sub'] != NULL || $jwtPayloadData['scope'] != NULL) {
        $settings['dxprLicenseInfo'] = $jwtPayloadData;
      }
    }

    if ($this->currentUser->id() != 0) {
      // Check if DXPR User id exists in JWT.
      $dxpr_user_exists = isset($jwtPayloadData) ? array_key_exists('sub', $jwtPayloadData) : FALSE;
      // Get enabled modules.
      $module_list = $this->moduleHandler->getModuleList();

      $settings['dxprUserInfo'] = [
        'local_email' => $this->currentUser->getEmail(),
        'local_uid' => $this->currentUser->id(),
        'local_username' => $this->currentUser->getDisplayName(),
        'default_theme' => $this->themeHandler->getDefault(),
        'active_theme' => $this->themeManager->getActiveTheme()->getName(),
        'drupal_version' => \Drupal::VERSION,
        'installed_modules' => array_keys($module_list),
        'dxpr_user_id' => $dxpr_user_exists ? $jwtPayloadData['sub'] : NULL,
      ];
    }

    $element['#attached']['library'][] = 'dxpr_builder/core';
    if (!$enable_editor) {
      if ($mode == 'dynamic') {
        if ($config->get('editor_assets_source', 0) == 2) {
          $element['#attached']['library'][] = 'dxpr_builder/editor.frontend_dev';
        }
        else {
          $element['#attached']['library'][] = 'dxpr_builder/editor.frontend';
        }
      }
    }

    if ($config->get('editor_assets_source', 0) == 2) {
      $settings['dxprDevelopment'] = TRUE;
    }

    if ($config->get('bootstrap', 0) == 1) {
      $element['#attached']['library'][] = 'dxpr_builder/bootstrap_full';
    }
    elseif ($config->get('bootstrap', 0) == 2) {
      $element['#attached']['library'][] = 'dxpr_builder/bootstrap_light';
    }

    if ($enable_editor) {
      $this->dxprBuilderService->editorAttach($element, $settings);
    }
    else {
      $settings['disallowContainers'][] = $container_name;
    }

    $settings['mediaBrowser'] = $config->get('media_browser', FALSE);
    if ($settings['mediaBrowser'] != '') {
      // Attach Entity Browser Configurations and libraries.
      $element['#attached']['drupalSettings']['entity_browser'] = [
        'dxprBuilderSingle' => [
          'cardinality' => 1,
          'selection_mode' => 'selection_append',
          'selector' => FALSE,
        ],
        'dxprBuilderMulti' => [
          'cardinality' => -1,
          'selection_mode' => 'selection_append',
          'selector' => FALSE,
        ],
      ];
      $element['#attached']['library'][] = 'entity_browser/common';
    }

    if ($this->moduleHandler->moduleExists('color')) {
      if ($palette = $this->colorGetPalette()) {
        $settings['palette'] = array_slice($palette, 0, 10);
      }
    }

    // Related to DXPR analytics service.
    $settings['recordAnalytics'] = FALSE;
    if ($config->get('record_analytics', TRUE)) {
      $settings['recordAnalytics'] = $config->get('record_analytics', TRUE);
    }

    // Related to DXPR notifications.
    if ($config->get('notifications', TRUE)) {
      $settings['notifications'] = $config->get('notifications', TRUE);
    }

    $element['#attached']['drupalSettings']['dxprBuilder'] = $settings;
  }

  /**
   * Wrapper for drupal_get_path()
   *
   * @param string $type
   *   The type of path to return, module or theme.
   * @param string $name
   *   The name of the theme/module to look up.
   *
   * @return string
   *   The path to the given module/theme
   *
   * @see drupal_get_path()
   */
  private function getPath($type, $name) {
    $paths = &drupal_static(__CLASS__ . '::' . __FUNCTION__, []);
    $key = $type . '::' . $name;
    if (!isset($paths[$key])) {
      $paths[$key] = $this->extensionPathResolver->getPath($type, $name);
    }

    return $paths[$key];
  }

  /**
   * Get the theme color pallette for the current theme.
   */
  private function colorGetPalette() {
    $default_theme = $this->configFactory->get('system.theme')->get('default');

    return color_get_palette($default_theme);
  }

}
