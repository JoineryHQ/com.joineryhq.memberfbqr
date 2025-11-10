<?php

require_once 'memberfbqr.civix.php';

use CRM_Memberfbqr_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function memberfbqr_civicrm_config(&$config): void {
  _memberfbqr_civix_civicrm_config($config);

  // This hook sometimes runs twice
  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;

  Civi::dispatcher()->addListener('civi.token.list', '_memberfbqr_token_list', -100);
  Civi::dispatcher()->addListener('civi.token.eval', '_memberfbqr_token_eval', -100);
}

function _memberfbqr_token_list(\Civi\Token\Event\TokenRegisterEvent $e) {
  $e->entity('membership')
    ->register('statusQrImageUrl', ts('Status link QR image URL'));
}

function _memberfbqr_token_eval(\Civi\Token\Event\TokenValueEvent $e) {
  $tokensInUse = $e->getTokenProcessor()->getMessageTokens();
  $hasMyToken = [
    'membership.statusQrImageUrl' => in_array('statusQrImageUrl', ($tokensInUse['membership'] ?? [])),
  ];
  foreach ($e->getRows() as $row) {
    $membershipId = $row->context['membershipId'];
    /** @var TokenRow $row */
    // $row->format('text/html');

    // FIXME: add tokens for short-date-format 'member since' and 'member expiration' dates.

    if ($hasMyToken['membership.statusQrImageUrl']) {
      $time = time();
      $hash = CRM_Memberfbqr_Page_MemberStatusQrImage::generateHash($membershipId, $time);
      $queryParams = [
        'm' => $membershipId,
        't' => $time,
        'h' => $hash,
      ];
      $row->tokens('membership', 'statusQrImageUrl', CRM_Utils_System::url('civicrm/memberfbqr/memberStatusQrImage', $queryParams, TRUE, NULL, FALSE, TRUE));
    }
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function memberfbqr_civicrm_install(): void {
  _memberfbqr_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function memberfbqr_civicrm_enable(): void {
  _memberfbqr_civix_civicrm_enable();
}
