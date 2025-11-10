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

### CiviCRM tokens
- **Status link QR image URL (`{membership.statusQrImageUrl}`):** prints the URL for
  an image representing a QR code which itself points to a configured FromBuilder
  search form including a hashed value for the relevant memership ID.
  - This is expected to be used when generating membership cards, each card bearing
  a QR code that points to a "Current Membership Status" page for that member.
  The generated URL is itself temporary, lasting only a few seconds, during which
  time unauthenticated users (such as the PDF creation package) can access it for
  inclusion in a PDF.

## Usage
The expected use case is to include something like this in the Message Template:
```
<img id="membershipStatusQR" alt="" src="{membership.statusQrImageUrl}" />
```

The PDF generation process will then have access to the QR code at the generated
URL, for inclusion in your PDF.

For debugging, you may also wish to print this URL "in the clear" in your Message
Template, so that you can easily view the raw image at the given URL. For example:  
```
<p><a href="{membership.statusQrImageUrl}">Link to QR code image</a></p>
```

Also note:

- This token only works within the scope of memberships, so something like the 
  "Print/Merge Document" action will only be useful if performed on a Membership
  search (not on a Contacts search).
- This token also works well within the use case of the Certificates extension,
  for printing on-demand membership cards, or something similar.

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
  // QR "quiet zone" size (empty, bg-colored border around the QR matrix), in modules.
  // If not specified, a value of '4' is used.
  'quietzoneSize' => 4,
  // Minimum size, in pixels, of the QR matrix (the grid of points, exclusive of
  // the quite zone). Note that the size of a module (a dot within the matrix)
  // will never be less than 4 pixels.
  'minimumMatrixWidth' => '',
  // machine name of FormBuilder form for member status
  'afformName' => 'afsearchMembershipStatus',
  // name of URL query parameter for member id, when viewing FormBuilder form
  // specified in `'afformName'`
  'memberIdParamName' => 'id',
];
```

## Notes on image dimensions
- The generated QR code will use the smallest QR _version_ capable of holding the
  given URL. E.g. QR version 1 uses a matrix of 21x21 modules (dots) and can store
  URLs up to 25 characters long. Longer URLs will need a higher QR version, which
  uses more modules.
- Module size is critical for readability. While a Version 1 QR code (21x21 modules)
  can typically be read easily at a total width of 84 pixels, a Version 7 QR code
  (capable of holding longer URLs) is unreadable below about 180px.
- Print size of an image is a mathematical conversion from pixels to dots (typically
  represented by a print resolution expressed in "dots per inch (dpi)", which
  amounts to "pixels per inch")
- For these reasons, this extension will never generate a QR code smaller than
  84px wide, and will never use a module (dot) size of less than 4px.
- If you don't configure the 'minimumMatrixWidth' setting, a module width of 4px
  will be used, ensuring good readability in most cases.
- If you want your QR codes to be larger, you can set the 'minimumMatrixWidth' to
  specify a minimum width of the generated QR image, in pixels. The generated
  image will never have a smaller pixel width than this value.
- For the generated URLs which are relatively longer, a higher QR version may be
  used, leading to a larger image.
- Due to the above considerations, this extension may generate QR images of varying
  sizes: never less than 84px, nor less than your 'minimumMatrixWidth' setting, but
  potentially larger.
- To ensure a) good QR code readability, and b) proper sizing of the image in your
  generated content (PDF, email, etc.), you are encouraged to:
  - Use CSS or other html-friendly properties to specify the desired final size.
  - Set a 'minimumMatrixWidth' value equal to this final size. (The resulting image
    may be larger than needed, in which case displaying it at your desired size
    won't result in blurring. On the other hand, if the resulting image is smaller
    than desired, your final display size may lead to blurring.)

## Support

Support for this plugin is handled under Joinery's ["As-Is Support" policy](https://joineryhq.com/software-support-levels#as-is-support).

Public issue queue for this plugin: https://github.com/JoineryHQ/com.joineryhq.memberfbqr/issues