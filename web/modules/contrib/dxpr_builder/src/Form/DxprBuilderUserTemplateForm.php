<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * DXPR Builder User Template form.
 *
 * @property \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
 */
class DxprBuilderUserTemplateForm extends EntityForm {

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
      '#description' => $this->t('Label for the dxpr builder user template.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dxpr_builder\Entity\DxprBuilderUserTemplate::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UID'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('uid'),
      '#description' => $this->t('User id for the dxpr builder user template.'),
      '#required' => TRUE,
    ];

    $form['template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template'),
      '#default_value' => $this->entity->get('template'),
      '#description' => $this->t('The dxpr builder user template body.'),
      '#required' => TRUE,
    ];

    $form['global'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Global'),
      '#default_value' => $this->entity->get('global'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new dxpr builder user template %label.', $message_args)
      : $this->t('Updated dxpr builder user template %label.', $message_args);
    $this->messenger()->addStatus($message);
    // Invalidate cache tags.
    $tags = Cache::mergeTags(['config:dxpr_builder.settings'], $this->entity->getCacheTags());
    Cache::invalidateTags($tags);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
