# SilverStripe Newsletter module

A Silverstripe extension enabling users to subscribe to a newsletter.

Currently only subscribing/unsubscribing functionality with management of recipients is implemented for external sending of newsletters.
Creating and sending the actual newsletters might be implemented in the future.

**Main features:**

- multiple newsletter lists (“channels”)
- subscribing to one or more channels via form
- Double-opt-in: confirm subscription via link, send by e-mail
- unsubscribing via generated link
- option to enable standard spam protection fields (using `silverstripe/spamprotection` extension)
- Admin: export confirmed subscribers per channel with necessary information for external bulk e-mail as CSV

## Requirements

Requires Silverstripe 6.x – for a version compatible with Silverstripe 5 see respective branch `5`

## Installation

Install with composer:

    composer require mhe/silverstripe-newsletter ^2.0

Perform `dev/build` task

## Usage overview

- In admin area “Newsletter” create channels as required (one default channel is auto-created)
- Include the subscription form in some page template (either on alle standard pages or create a dedicated page type)

  For all channels (user selection):
  ```
  $ChannelSubscriptionForm
  ```
  For a specific channel by name:
  ```
  $ChannelSubscriptionForm("Highlights")
  ```
- Subscribers can be found in the admin area “Newsletter”
  - Tab “Channels”: references the active and confirmed subscribers, perfect for export and usage in mailings
  - Tab “Recipients”: shows all subscribers, including unconfirmed ones, with detailed information, perfect for data cleanup etc.

## Configuration

### Silverstripe YAML configuration options

- `Mhe\Newsletter\Forms\SubscriptionForm.enable_spam_protection`: add spam protection to subscription forms, needs `silverstripe/spamprotection` extension (default: false)
- `Mhe\Newsletter\Forms\UnsubscribeForm.enable_spam_protection`: add spam protection to unsubscribe forms, needs `silverstripe/spamprotection` extension (default: false)
- `Mhe\Newsletter\Model\Recipient.autokey_length`: length of auto-generated URL keys (default: 40)
- `Mhe\Newsletter\Model\Recipient.autokey_chars`: characters to use in auto-generated URL keys (default: "abcdef0123456789")

### Templates

- `Mhe/Newsletter/Controllers/SubscriptionController_confirm.ss`: Content displayed after a recipient confirms their subscription by clicking the confirmation link
- `Mhe/Newsletter/Controllers/SubscriptionController_unsubscribe.ss`: Content displayed when clicking an unsubscribe link
- `Mhe/Newsletter/Email/SubscriptionConfirmationEmail.ss`: Content of the email sent after submitting the subscription form
