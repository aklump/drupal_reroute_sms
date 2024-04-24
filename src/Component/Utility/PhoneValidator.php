<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\Component\Utility;

class PhoneValidator implements PhoneValidatorInterface {

  public function isValid($number) {
    $formatted = (new FormatPhoneNumber())($number, FormatPhoneNumber::SMS_FORMAT);

    return preg_match('#\+1\d{10}#', $formatted);
  }

}
