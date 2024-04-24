<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\Component\Utility;

interface PhoneValidatorInterface {

  /**
   * Validates an phone number.
   *
   * @param string $number
   *   A string containing a phone number.
   *
   * @return bool
   *   TRUE if the number is valid.
   */
  public function isValid($number);

}
