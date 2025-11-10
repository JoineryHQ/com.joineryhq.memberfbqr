<?php


use CRM_Memberfbqr_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Memberfbqr_Utils_General {

  public static function getSetting($settingName, $default = NULL) {
    if (!isset(Civi::$statics[__CLASS__]['extSettings'])) {
      Civi::$statics[__CLASS__]['extSettings'] = \Civi::settings()->get(E::LONG_NAME);
    }
    $ret = (Civi::$statics[__CLASS__]['extSettings'][$settingName]);
    if (is_null(($ret) || $ret == '')) {
      $ret = $default;
    }
    return $ret;
  }

}
