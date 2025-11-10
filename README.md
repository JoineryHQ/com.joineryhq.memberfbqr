# CiviCRM: Membership FormBuilder QR Code

Provides for dynamic creation of a QR code pointing to a configured "member info"
FormBuilder form. This QR code is accessible through a CiviCRM token in the context
of Memberships, suitable for inclusion on membership cards generated, for example,
by the [Certificates](https://lab.civicrm.org/extensions/certificates) extension.

## Optional features

If the [FormBuilder hash filters](https://github.com/JoineryHQ/com.joineryhq.fbhash)
extension is enabled, this extension will attemp to use its features to hash the
destination URL before encoding it into the QR code.

## Functionality

The following functions are provided by this extension:

### CiviCRM tokens:
- Status link QR image URL (`{membership.statusQrImageUrl}`): prints the URL for
  an image representing a QR code which itself points to a configured FromBuilder
  search form including a hashed value for the relevant memership ID.  
  This is expected to be used when generating membership cards, each card bearing
  a QR code that points to a "Current Membership Status" page for that member.
  The generated URL is itself temporary, lasting only a few seconds, during which
  time unauthenticated users (such as the PDF creation package) can access it for
  inclusion in a PDF.

## Configuration
In lieu of a configuration UI, this extension uses settings defined in 
civicrm.settings.php. (A configuration UI would be nice to have, but does not yet exist.)

Example (to be added to civicrm.settings.php):
```php
global $civicrm_setting;
$civicrm_setting['com.joineryhq.memberfbqr']['com.joineryhq.memberfbqr'] = [
  // Lifespan, in seconds, of the url for the QR code image. If not spedified,
  // a value of '5' is used.
  'qrUrlMaxAgeSeconds' => 5,
  // background color for QR code, as an array of RGB values.
  'fgColor' => [200, 36, 36],
  // foreground color for QR code, as an array of RGB values.
  'bgColor' => [255, 255, 255],
  // QR "quiet zone" size (empty, bg-colored border around the QR body), in modules.
  // If not specified, a value of '4' is used.
  'quietzoneSize' => 4,
  // machine name of FormBuilder form for member status
  'afformName' => 'afsearchMembershipStatus',
  // name of URL query parameter for member id, when viewing FormBuilder form
  // specified in `'afformName'`
  'memberIdParamName' => 'id',
];
```

## Support

Support for this plugin is handled under Joinery's ["As-Is Support" policy](https://joineryhq.com/software-support-levels#as-is-support).

Public issue queue for this plugin: https://github.com/JoineryHQ/com.joineryhq.memberfbqr/issues