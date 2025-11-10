<?php


use CRM_Memberfbqr_ExtensionUtil as E;

/**
 * General-purpose utilities for stepw extension.
 *
 */
class CRM_Memberfbqr_Utils_General {

  public static function getSetting($settingName) {
    if (!isset(Civi::$statics[__CLASS__]['extSettings'])) {
      Civi::$statics[__CLASS__]['extSettings'] = \Civi::settings()->get(E::LONG_NAME);
    }
    return (Civi::$statics[__CLASS__]['extSettings'][$settingName] ?? NULL);
  }

}
