<?php
// SPDX-License-Identifier: GPL-2.0-or-later
namespace Drupal\reroute_sms\Component\Utility;

class FormatPhoneNumber {

  const FORMAT = '(%d) %d-%d';

  const SMS_FORMAT = '+1%d%d%d';

  public function __invoke(string $number, string $format = NULL) {
    $number = preg_replace('#[^0-9]#', '', $number);
    preg_match('#(.+)?(\d{3})(\d{3})(\d{4})$#', $number, $matches);
    array_shift($matches);
    array_shift($matches);
    $matches = array_filter($matches);
    $format = $format ?? self::FORMAT;
    array_unshift($matches, $format);

    return call_user_func_array('sprintf', $matches);
  }
}

