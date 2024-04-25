<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\Form;

use Drupal\Component\Utility\PhoneValidatorInterface;
use Drupal\reroute_sms\Component\Utility\PhoneValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\reroute_sms\Constants\RerouteSMSConstants;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a settings form for Reroute SMS configuration.
 */
class SettingsForm extends ConfigFormBase implements TrustedCallbackInterface {

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $rerouteConfig;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The phone number validator.
   *
   * @var \Drupal\Component\Utility\PhoneValidatorInterface
   */
  protected $phoneValidator;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_sms_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['reroute_sms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['textareaRowsValue'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      new PhoneValidator(),
      $container->get('extension.list.module')
    );
  }

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\reroute_sms\Component\Utility\PhoneValidatorInterface $phone_validator
   *   The phone number validator.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    RoleStorageInterface $role_storage,
    \Drupal\reroute_sms\Component\Utility\PhoneValidatorInterface $phone_validator,
    ModuleExtensionList $extension_list_module
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->roleStorage = $role_storage;
    $this->phoneValidator = $phone_validator;
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->get('reroute_sms.settings');
    $form[RerouteSMSConstants::REROUTE_SMS_ENABLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable rerouting'),
      '#default_value' => $config->get(RerouteSMSConstants::REROUTE_SMS_ENABLE),
      '#description' => $this->t('Check this box if you want to enable sms rerouting. Uncheck to disable rerouting.'),
      '#config' => [
        'key' => 'reroute_sms.settings:' . RerouteSMSConstants::REROUTE_SMS_ENABLE,
      ],
    ];

    $states = [
      'visible' => [':input[name=' . RerouteSMSConstants::REROUTE_SMS_ENABLE . ']' => ['checked' => TRUE]],
    ];

    $default_address = $config->get(RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER);
    if (NULL === $default_address) {
      $default_address = $this->config('system.site')->get('mail');
    }

    $form[RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Rerouting phone numbers'),
      '#default_value' => $default_address,
      '#description' => $this->t('Provide a comma-delimited list of phone numbers. Every destination phone number will be rerouted to these addresses.<br/>If this field is empty and no value is provided, all outgoing SMS texts would be aborted and the SMS would be recorded in the recent log entries (if enabled).'),
      '#element_validate' => [
        [$this, 'validateMultiplePhoneNumbers'],
        [$this, 'validateMultipleUnique'],
      ],
      '#reroute_config_delimiter' => ',',
      '#pre_render' => [[$this, 'textareaRowsValue']],
      '#states' => $states,
      '#config' => [
        'key' => 'reroute_sms.settings:' . RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER,
      ],
    ];

    $form[RerouteSMSConstants::REROUTE_SMS_MESSAGE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a Drupal status message after rerouting'),
      '#default_value' => $config->get(RerouteSMSConstants::REROUTE_SMS_MESSAGE),
      '#description' => $this->t('Check this box if you would like a Drupal status message to be displayed to users after submitting an SMS to let them know it was aborted to send or rerouted to a different phone number.'),
      '#states' => $states,
      '#config' => [
        'key' => 'reroute_sms.settings:' . RerouteSMSConstants::REROUTE_SMS_MESSAGE,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Adjust rows value according to the content size.
   *
   * @param array $element
   *   The render array to add the access denied message to.
   *
   * @return array
   *   The updated render array.
   */
  public static function textareaRowsValue(array $element): array {
    $size = mb_substr_count($element['#default_value'] ?? '', PHP_EOL) + 1;
    if ($size > $element['#rows']) {
      $element['#rows'] = min($size, 10);
    }

    return $element;
  }

  /**
   * Validate multiple phone numbers field.
   *
   * @param array $element
   *   A field array to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateMultiplePhoneNumbers(array $element, FormStateInterface $form_state): void {
    // Allow only valid phone numbers.
    $numbers = reroute_sms_split_string($form_state->getValue($element['#name']));
    foreach ($numbers as $number) {
      if (!$this->phoneValidator->isValid($number)) {
        $form_state->setErrorByName($element['#name'], $this->t('@phone_number is not a valid phone number.', ['@phone_number' => $number]));
      }
    }
  }

  /**
   * Validate multiple phone numbers field.
   *
   * @param array $element
   *   A field array to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateMultipleUnique(array $element, FormStateInterface $form_state): void {
    // String "SMS@example.com; ;; , ,," save just as "SMS@example.com".
    // This will be ignored if any validation errors occur.
    $form_state->setValue($element['#name'], implode($element['#reroute_config_delimiter'] ?? PHP_EOL, reroute_sms_split_string($form_state->getValue($element['#name']))));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('reroute_sms.settings')
      ->set(RerouteSMSConstants::REROUTE_SMS_ENABLE, $form_state->getValue(RerouteSMSConstants::REROUTE_SMS_ENABLE))
      ->set(RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER, $form_state->getValue(RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER))
      ->set(RerouteSMSConstants::REROUTE_SMS_MESSAGE, $form_state->getValue(RerouteSMSConstants::REROUTE_SMS_MESSAGE))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
