<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/twilio.php';
use Twilio\Rest\Client;

try {
    $client = new Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
    $message = $client->messages->create(
        '+251916273479', // To (use a verified number for trial accounts)
        [
            'from' => TWILIO_PHONE_NUMBER,
            'body' => 'Test SMS from Werabe Land Lease System'
        ]
    );
    echo "SMS sent successfully! SID: " . $message->sid;
} catch (Exception $e) {
    echo "Error sending SMS: " . $e->getMessage();
}
?>