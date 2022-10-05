<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DXPR Builder Profile form.
 *
 * @property \Drupal\dxpr_builder\DxprBuilderProfileInterface $entity
 */
class DxprBuilderProfileForm extends EntityForm {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a DxprBuilderProfileForm.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the dxpr builder profile.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dxpr_builder\Entity\DxprBuilderProfile::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['dxpr_editor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start Editor When Page Loads'),
      '#default_value' => $this->entity->get('dxpr_editor'),
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => 10,
      '#default_value' => $this->entity->get('weight'),
    ];

    $form['roles_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('If a user has one of the selected roles his DXPR Builder interface will be limited to the elements and buttons selected in this profile. New blocks and views are not included automatically.'),
    ];
    $options = [];
    foreach (user_roles(TRUE) as $role_id => $role) {
      $options[$role_id] = $role->label();
    }

    $form['roles_wrapper']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? [] : $this->entity->get('roles'),
    ];

    $form['elements_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Elements'),
    ];
    $options = self::getElements();
    $form['elements_wrapper']['elements'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Elements'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? array_keys($options) : $this->entity->get('elements'),
    ];

    $form['blocks_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
    ];

    $form['blocks_wrapper']['all_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check/Uncheck all Blocks'),
    ];

    $blacklist = [
      // These two blocks can only be configured in display variant plugin.
      // @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
      'page_title_block',
      'system_main_block',
      // Fallback plugin makes no sense here.
      'broken',
    ];
    $definitions = $this->blockManager->getDefinitions();
    $options = [];
    foreach ($definitions as $block_id => $definition) {
      $hidden = !empty($definition['_block_ui_hidden']);
      $blacklisted = in_array($block_id, $blacklist);
      $is_view = ($definition['provider'] == 'views');
      $is_ctools = ($definition['provider'] == 'ctools');
      if ($hidden || $blacklisted or $is_view or $is_ctools) {
        continue;
      }
      $options['az_block-' . $block_id] = ucfirst($definition['category']) . ': ' . $definition['admin_label'];
    }
    $form['blocks_wrapper']['blocks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Blocks'),
      '#options' => $options,
      '#default_value' => $this->entity->isNew() ? array_keys($options) : $this->entity->get('blocks'),
    ];

    $form['views_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Views'),
    ];

    $form['views_wrapper']['all_views'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check/Uncheck all Views'),
    ];

    $views_elements = [];
    $views = Views::getAllViews();
    foreach ($views as $view) {
      if (!$view->status()) {
        continue;
      }
      $executable_view = Views::getView($view->id());
      $executable_view->initDisplay();
      foreach ($executable_view->displayHandlers as $id => $display) {
        $key = 'az_view-' . $executable_view->id() . '-' . $id;
        $views_elements[$key] = $view->label() . ': ' . $display->display['display_title'];
      }
    }
    $form['views_wrapper']['views'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Views'),
      '#options' => $views_elements,
      '#default_value' => $this->entity->isNew() ? array_keys($views_elements) : $this->entity->get('views'),
    ];

    $form['inline_buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Editor buttons (inline editing)'),
      '#tree' => TRUE,
      '#attributes' => ['class' => ['cke_ltr']],
    ];
    $buttons = $this->entity->isNew() ?
      self::getInlineButtons() : $this->entity->get('inline_buttons');
    foreach (self::getAllButtons() as $button => $title) {
      $form['inline_buttons'][$button] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => in_array($button, $buttons),
        // Add a button icon near to the checkbox.
        '#field_suffix' => sprintf('<span class="cke_button_icon cke_button__%s_icon"></span>', strtolower($button)),
      ];
    }

    $form['modal_buttons'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Editor buttons (modal editing)'),
      '#tree' => TRUE,
      '#attributes' => ['class' => ['cke_ltr']],
    ];
    $buttons = $this->entity->isNew() ?
      self::getModalButtons() : $this->entity->get('modal_buttons');
    foreach (self::getAllButtons() as $button => $title) {
      $form['modal_buttons'][$button] = [
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => in_array($button, $buttons),
        // Add a button icon near to the checkbox.
        '#field_suffix' => sprintf('<span class="cke_button_icon cke_button__%s_icon"></span>', strtolower($button)),
      ];
    }

    $form['#attached']['library'][] = 'dxpr_builder/configuration.profileform';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Make the roles export more readable.
    $values['roles'] = array_values(array_filter($values['roles']));
    $values['elements'] = array_values(array_filter($values['elements']));
    $values['blocks'] = array_values(array_filter($values['blocks']));
    $values['views'] = array_values(array_filter($values['views']));
    $values['inline_buttons'] = array_keys(array_filter($values['inline_buttons']));
    $values['modal_buttons'] = array_keys(array_filter($values['modal_buttons']));
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $result = parent::save($form, $form_state);
    var_dump($result);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new dxpr builder profile %label.', $message_args)
      : $this->t('Updated dxpr builder profile %label.', $message_args);
    $this->messenger()->addStatus($message);
    // Invalidate cache tags.
    $tags = Cache::mergeTags(['config:dxpr_builder.settings'], $this->entity->getCacheTags());
    Cache::invalidateTags($tags);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Returns element options.
   */
  public function getElements() {
    return [
      'az_accordion' => $this->t('Accordion'),
      'az_alert' => $this->t('Alert'),
      'az_blockquote' => $this->t('Blockquote'),
      'az_button' => $this->t('Button'),
      'az_circle_counter' => $this->t('Circle Counter'),
      'az_countdown' => $this->t('Countdown'),
      'az_counter' => $this->t('Counter'),
      'az_html' => $this->t('HTML'),
      'az_icon' => $this->t('Icon'),
      'az_image' => $this->t('Image'),
      'az_images_carousel' => $this->t('Image Carousel'),
      'az_jumbotron' => $this->t('Jumbotron'),
      'az_link' => $this->t('Link'),
      'az_map' => $this->t('Map'),
      'az_panel' => $this->t('Panel'),
      'az_progress_bar' => $this->t('Progress Bar'),
      'az_separator' => $this->t('Separator'),
      'az_text' => $this->t('Text'),
      'az_video' => $this->t('Video'),
      'az_video_local' => $this->t('Local Video'),
      'az_well' => $this->t('Well'),
      'az_carousel' => $this->t('Carousel'),
      'az_container' => $this->t('Container'),
      'az_layers' => $this->t('Layers'),
      'az_row' => $this->t('Row'),
      'az_section' => $this->t('Section'),
      'st_social' => $this->t('Social Links'),
      'az_tabs' => $this->t('Tabs'),
      'az_toggle' => $this->t('Toggle'),
    ];
  }

  /**
   * Returns all available CKEditor buttons.
   */
  protected static function getAllButtons() {
    return [
      'Bold' => 'Bold',
      'Italic' => 'Italic',
      'Underline' => 'Underline',
      'Strike' => 'Strike through',
      'JustifyLeft' => 'Align left',
      'JustifyCenter' => 'Center',
      'JustifyRight' => 'Align right',
      'JustifyBlock' => 'Justify',
      'BulletedList' => 'Insert/Remove Bullet list',
      'NumberedList' => 'Insert/Remove Numbered list',
      'BidiLtr' => 'Left-to-right',
      'BidiRtl' => 'Right-to-left',
      'Outdent' => 'Outdent',
      'Indent' => 'Indent',
      'Undo' => 'Undo',
      'Redo' => 'Redo',
      'Link' => 'Link',
      'Unlink' => 'Unlink',
      'Anchor' => 'Anchor',
      'Image' => 'Image',
      'TextColor' => 'Text color',
      'BGColor' => 'Background color',
      'Superscript' => 'Superscript',
      'Subscript' => 'Subscript',
      'Blockquote' => 'Block quote',
      'Source' => 'Source code',
      'HorizontalRule' => 'Horizontal rule',
      'Cut' => 'Cut',
      'Copy' => 'Copy',
      'Paste' => 'Paste',
      'PasteText' => 'Paste Text',
      'PasteFromWord' => 'Paste from Word',
      'ShowBlocks' => 'Show blocks',
      'RemoveFormat' => 'Remove format',
      'SpecialChar' => 'Character map',
      'Format' => 'HTML block format',
      'Font' => 'Font',
      'FontSize' => 'Font size',
      'Styles' => 'Font style',
      'Table' => 'Table',
      'SelectAll' => 'Select all',
      'Find' => 'Search',
      'Replace' => 'Replace',
      'Smiley' => 'Smiley',
      'CreateDiv' => 'Div container',
      'Iframe' => 'IFrame',
      'Maximize' => 'Maximize',
      'SpellChecker' => 'Check spelling',
      'Scayt' => 'Spell check as you type',
      'About' => 'About',
      'Templates' => 'Templates',
      'CopyFormatting' => 'Copy Formatting',
      'NewPage' => 'New page',
      'Preview' => 'Preview',
      'PageBreak' => 'Page break',
    ];
  }

  /**
   * Returns default buttons for inline mode.
   */
  protected static function getInlineButtons() {
    return [
      'Bold',
      'Italic',
      'RemoveFormat',
      'TextColor',
      'Format',
      'Styles',
      'FontSize',
      'JustifyLeft',
      'JustifyCenter',
      'JustifyRight',
      'JustifyBlock',
      'BulletedList',
      'Link',
      'Unlink',
      'Image',
      'Table',
      'Undo',
      'Redo',
    ];
  }

  /**
   * Returns default buttons form modal mode.
   */
  protected static function getModalButtons() {
    return [
      'Bold',
      'Italic',
      'Underline',
      'Strike',
      'Superscript',
      'Subscript',
      'RemoveFormat',
      'JustifyLeft',
      'JustifyCenter',
      'JustifyRight',
      'JustifyBlock',
      'BulletedList',
      'NumberedList',
      'Outdent',
      'Indent',
      'Blockquote',
      'CreateDiv',
      'Undo',
      'Redo',
      'PasteText',
      'PasteFromWord',
      'Link',
      'Unlink',
      'Image',
      'HorizontalRule',
      'SpecialChar',
      'Table',
      'Templates',
      'TextColor',
      'Source',
      'ShowBlocks',
      'Maximize',
      'Format',
      'Styles',
      'FontSize',
      'Scayt',
    ];
  }

}
