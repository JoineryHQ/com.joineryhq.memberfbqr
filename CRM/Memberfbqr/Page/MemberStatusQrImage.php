<?php

use CRM_Memberfbqr_ExtensionUtil as E;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;

require E::path('vendor/autoload.php');

class CRM_Memberfbqr_Page_MemberStatusQrImage extends CRM_Core_Page {

  private $membershipId;
  private $timestamp;
  private $hash;
  private $maxAgeSeconds;

  const afformName = 'afsearchMembershipStatus';

  public function run() {
    $this->maxAgeSeconds = CRM_Memberfbqr_Utils_General::getSetting('qrUrlMaxAgeSeconds', 5);

    // Send image headers (we're always called as the URL for an image).
    header('Content-Type: image/png');

    // Get settings.
    $fgColor = CRM_Memberfbqr_Utils_General::getSetting('fgColor');
    $bgColor = CRM_Memberfbqr_Utils_General::getSetting('bgColor');
    $afformName = CRM_Memberfbqr_Utils_General::getSetting('afformName');
    $memberIdParamName = CRM_Memberfbqr_Utils_General::getSetting('memberIdParamName');
    $quietzoneSize = CRM_Memberfbqr_Utils_General::getSetting('quietzoneSize', 4);

    // If settings are incomplete, log error and sent an error image.
    if (empty($afformName) || empty($memberIdParamName)) {
      Civi::log()->error(E::SHORT_NAME . ": Could not generate QR image; requires configuration of afformName and memberIdParamName settings.");
      $errorImagePath = E::path('img/error.png');
      readfile($errorImagePath);
      exit;
    }
    // If we're still here, continue processing.

    // Set variables from query params.
    $this->membershipId = CRM_Utils_Request::retrieve('m', 'String');
    $this->timestamp = CRM_Utils_Request::retrieve('t', 'String');
    $this->hash = CRM_Utils_Request::retrieve('h', 'String');

    // This page needs to be publicly accessible, but not TOO public.
    // A secure key (generated in the token membership.statusQrImageUrl) is in the url, and checked here.
    if (!$this->_allowAccess()) {
      CRM_Utils_System::permissionDenied();
    }

    $scale = $this->_calculateScale();

    // If we're still here, go ahead and build the QR image.
    $options = new QROptions([
      'outputType' => QRGdImagePNG::GDIMAGE_PNG,
      // scale: even a version-1 qr will be > 290px at this scale (called for in our PDF design, which will CSS-force the image to 290px)
      'scale' => $scale,
      'imageBase64' => FALSE,
      'moduleValues' => [
        // finder dark (true)
        QRMatrix::M_FINDER_DARK => $fgColor,
         // finder dot, dark (true)
        QRMatrix::M_FINDER_DOT => $fgColor,
         // light (false), white is the transparency color and is enabled by default
        QRMatrix::M_FINDER => $bgColor,
        // alignment
        QRMatrix::M_ALIGNMENT_DARK => $fgColor,
        QRMatrix::M_ALIGNMENT => $bgColor,
        // timing
        QRMatrix::M_TIMING_DARK => $fgColor,
        QRMatrix::M_TIMING => $bgColor,
        // format
        QRMatrix::M_FORMAT_DARK => $fgColor,
        QRMatrix::M_FORMAT => $bgColor,
        // version
        QRMatrix::M_VERSION_DARK => $fgColor,
        QRMatrix::M_VERSION => $bgColor,
        // data
        QRMatrix::M_DATA_DARK => $fgColor,
        QRMatrix::M_DATA => $bgColor,
        // darkmodule
        QRMatrix::M_DARKMODULE => $fgColor,
        // separator
        QRMatrix::M_SEPARATOR => $bgColor,
        // quietzone
        QRMatrix::M_QUIETZONE => $bgColor,
        // logo (requires a call to QRMatrix::setLogoSpace()), see QRImageWithLogo
        QRMatrix::M_LOGO => $bgColor,
      ],
      'quietzoneSize' => $quietzoneSize,
    ]);

    $qr_code = new QRCode($options);
    $filters = [$memberIdParamName => $this->membershipId];
    if (CRM_Extension_System::singleton()->getManager()->isEnabled('com.joineryhq.fbhash')) {
      $fbhash = \Civi\Api4\Fbhash::hashAfformUrl()
        ->setCheckPermissions(FALSE)
        ->setFilters($filters)
        ->setAfformName($afformName)
        ->execute()
        ->first();
      $link = $fbhash['url'];
    }
    else {
      $afform = \Civi\Api4\Afform::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('name', '=', $afformName)
        ->setLimit(1)
        ->execute()
        ->first();
      if (empty($afform)) {
        throw new \CRM_Core_Exception('Afform not found by name: ' . $afformName);
      }
      $link = \CRM_Utils_System::url($afform['server_route'], NULL, TRUE, NULL, FALSE, ($afform['is_public'] ?? FALSE));
      $link .= '#/?' . http_build_query($filters);
    }
    $qr_code_data = $qr_code->render($link);
    // Send the raw URL in a header for easier debugging.
    header("X-memberfbqr-link-: $link");
    // Send the raw image data (image headers were sent above).
    echo($qr_code_data);
    exit;
  }

  private function _allowAccess() {
    // m is a required query param.
    if (empty($this->membershipId)) {
      return FALSE;
    }
    // Test if user can view requested membership record:
    try {
      $result = Civi\Api4\Membership::get()
        ->addWhere('id', '=', $this->membershipId)
        ->setCheckPermissions(TRUE)
        ->execute();
    }
    catch (CRM_Core_Exception $e) {
      // Any problems here, assume no access; we'll check based on hash/time below.
    }

    if ($result && $result->count()) {
      return TRUE;
    }

    // If we're still here, h and t are required query params.
    if ($this->hash && $this->timestamp) {
      // Validate the hash.
      if ($this->hash == self::_generateHash($this->membershipId, $this->timestamp)) {
        $currentTimestamp = time();
        $elapsedSeconds = ($currentTimestamp - $this->timestamp);
        if ($elapsedSeconds < $this->maxAgeSeconds) {
          return TRUE;
        }
      }
    }

    // If we're still here, deny access.
    return FALSE;
  }

  private function _calculateScale() {
    // Mathematical considerations:
    // QR version is auto-calculated by chillerlan library; version-1 QRs will be
    // the smallest, so we'll assume v1 (21 modules wide). If data requires a higher
    // QR version, images may be significantly larger.
    $minimumMatrixWidth = CRM_Memberfbqr_Utils_General::getSetting('minimumMatrixWidth');

    $scale = ceil($minimumMatrixWidth / 21);

    if (is_null($scale) || (int) $scale < 4) {
      $scale = 4;
    }
    $scale = (int) $scale;
    return $scale;
  }

  public static function _generateHash($mid, $time = NULL) {
    if (is_null($time)) {
      $time = time();
    }
    $hash = hash('sha256', $time . $mid . CIVICRM_SITE_KEY);
    return $hash;
  }

}
