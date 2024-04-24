<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\EventSubscriber;

use Drupal;
use Drupal\reroute_sms\Constants\RerouteSMSConstants;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RerouteSmsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    // @link https://www.drupal.org/project/drupal/issues/2825358
    if (class_exists(SmsEvents::CLASS)) {
      $events[SmsEvents::MESSAGE_OUTGOING_PRE_PROCESS][] = [
        'onMessageOutgoingPreProcess',
        0,
      ];
    }

    return $events;
  }

  /**
   * Respond to a new sms message event.
   *
   * @param SmsMessageEvent $event
   *   A new event instance.
   */
  public function onMessageOutgoingPreProcess(SmsMessageEvent $event) {
    $config = Drupal::config('reroute_sms.settings');
    $is_enabled = $config->get(RerouteSMSConstants::REROUTE_SMS_ENABLE);
    if (!$is_enabled) {
      return;
    }

    $rerouting_numbers = $config->get(RerouteSMSConstants::REROUTE_SMS_PHONE_NUMBER);
    $rerouting_numbers = reroute_sms_split_string($rerouting_numbers);

    $messages = $event->getMessages();
    foreach ($messages as $message) {
      $original = $message->getRecipients();
      $message->removeRecipients($original);
      $base_context = [
        'action' => 'aborted',
        'message' => $message->getMessage(),
        'original' => implode(', ', $original),
        'rerouted_to' => NULL,
      ];
      $contexts = [];
      foreach ($rerouting_numbers as $reroute_number) {
        $message->addRecipient($reroute_number);
        $contexts[] = [
            'action' => 'rerouted',
            'rerouted_to' => $reroute_number,
          ] + $base_context;
      }
      if (empty($contexts)) {
        $contexts[] = $base_context;
      }
      foreach ($contexts as $context) {
        Drupal::logger('reroute_sms')
          ->notice('An SMS was either rerouted or aborted.<br/>Detailed email data: Array $context <pre>@context</pre>', [
            '@context' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
          ]);
      }
    }
    if ($config->get(RerouteSMSConstants::REROUTE_SMS_MESSAGE)) {
      Drupal::messenger()
        ->addMessage(t('An SMS either aborted or rerouted to the configured address. Site administrators can check the recent log entries for complete details on the rerouted SMS. For more details please refer to Reroute SMS settings.'));
    }
  }

}
