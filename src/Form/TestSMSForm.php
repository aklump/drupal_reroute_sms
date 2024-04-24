<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sms\Direction;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Provider\SmsProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a form to test Reroute SMS.
 */
class TestSMSForm extends FormBase {

  /** @var \Drupal\sms\Provider\SmsProviderInterface */
  protected $smsProvider;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_sms_test_email_form';
  }

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sms.provider'),
      $container->get('messenger'),
    );
  }

  /**
   * Constructs a new object.
   *
   * @param \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   *   Mail manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(SmsProviderInterface $sms_provider, MessengerInterface $messenger) {
    $this->smsProvider = $sms_provider;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      'addresses' => [
        '#type' => 'fieldset',
        '#description' => $this->t('A list of phone numbers separated by a comma could be submitted.<br/>Phone numbers are not validated: any valid or invalid phone number format could be submitted.'),
        'recipients' => [
          '#type' => 'textfield',
          '#title' => $this->t('Recipients'),
        ],
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => $this->t('Message'),
        '#default_value' => $this->t('Reroute SMS message'),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send SMS'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $recipients = $form_state->getValue(['recipients']);
    $recipients = reroute_sms_split_string($recipients);
    $param_keys = ['body'];
    $params = array_intersect_key($form_state->getValues(), array_flip($param_keys));

    $sms = (new SmsMessage())
      ->addRecipients($recipients)
      ->setMessage($params['body'])
      ->setDirection(Direction::OUTGOING);
    $results = $this->smsProvider->send($sms);
    foreach ($results as $result) {
      $recipient_number = $result->getRecipients()[0];
      $report = $result->getReport($recipient_number);
      $status = $report->getStatus();
      if ($status !== SmsMessageReportStatus::DELIVERED) {
        $this->messenger->addWarning($this->t('Test SMS sent to %number returnes non-delivered status of: %status.', [
          '%number' => $recipient_number,
          '%status' => $status,
        ]));
      }
    }
  }

}
