# SilverStripe Newsletter module

A Silverstripe extension enabling users to subscribe to a newsletter.

Currently only subscribing/unsubscribing functionality with management of recipients is implemented for external sending of newsletters.
Creating and sending the actual newsletters might be implemented in the future.

**Main features:**

- multiple newsletter lists (“channels”)
- subscribing to one or more channels via form
- Double-opt-in: confirm subscription via link, send by e-mail
- unsubscribing via generated link
- Admin: export confirmed subscribers per channel with necessary information for external bulk e-mail as CSV

## Requirements

Requires Silverstripe 6.x – for a version compatible with Silverstripe 5 see respective branch `5`

## Installation

Install with composer:

    composer require mhe/silverstripe-newsletter

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
