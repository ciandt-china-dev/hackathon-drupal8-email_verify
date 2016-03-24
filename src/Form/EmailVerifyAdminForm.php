<?php

/**
 * @file
 * Contains \Drupal\email_verify\Form\EmailVerifyAdminForm.
 */

namespace Drupal\email_verify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\email_verify\EmailVerifyManager;
use Drupal\email_verify\EmailVerifyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailVerifyAdminForm extends ConfigFormBase {

  protected $connect;

  /**
   * The email verify manager.
   *
   * @var \Drupal\email_verify\BookManagerInterface
   */
  protected $emailVerifyManager;

  /**
   * Constructs a new EmailVerifyAdminForm.
   *
   * @param \Drupal\email_verify\EmailVerifyManagerInterface $email_verify_manager
   *   The email verify manager.
   */
  public function __construct(EmailVerifyManagerInterface $email_verify_manager) {
    $this->emailVerifyManager = $email_verify_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email_verify.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_verify_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['email_verify.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_verify.settings');

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Email Verify to verify email adresses'),
      '#default_value' => $config->get('active'),
      '#description' => $this->t('When enabled, Email Verify will check email addresses for validity.'),
    ];

    $form['email_verify'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Forms to check'),
      '#collapsible' => TRUE,
      '#description' => $this->t('Check the boxes for the forms you want to have this module check email addresses on.'),
    ];

    $form['email_verify']['user_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User registration'),
      '#default_value' => $config->get('user_registration'),
    ];

    $form['email_verify']['user_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User profile'),
      '#default_value' => $config->get('user_profile'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('contact')) {
      $form['email_verify']['site_contact'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Site-wide contact'),
        '#default_value' => $config->get('site_contact'),
      ];
      $form['email_verify']['personal_contact'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Personal contact'),
        '#default_value' => $config->get('personal_contact'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($this->config('email_verify.settings')->get('active') !== 1 &&
      $form_state->getValue('active')) {
      if (!$this->emailVerifyManager->checkHost('dreqiudieuwbdiuewbfdiuwupal.org')) {
        $form_state->setErrorByName('active', $this->t('Email Verify will test email domains but not mailboxes because port 25 is closed on your host\'s firewall'));
        \Drupal::logger('email_verify')->notice('Email Verify cannot test mailboxes because port 25 is closed.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('email_verify.settings')
      ->set('active', $form_state->getValue('active'))
      ->set('user_registration', $form_state->getValue('user_registration'))
      ->set('user_profile', $form_state->getValue('user_profile'))
      ->set('site_contact', $form_state->getValue('site_contact'))
      ->set('personal_contact', $form_state->getValue('personal_contact'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
