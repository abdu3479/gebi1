```php
     <?php
     // Load dependencies
     require_once 'C:\xampp\htdocs\gebi1\vendor\autoload.php';
     use Twilio\Rest\Client;
     use Dotenv\Dotenv;

     // Initialize log file
     $log_file = 'C:\xampp\logs\sms_errors.log';
     $log_dir = dirname($log_file);
     if (!is_dir($log_dir)) {
         mkdir($log_dir, 0777, true) or die("Failed to create log directory: $log_dir");
     }
     if (!file_exists($log_file)) {
         file_put_contents($log_file, '') or die("Failed to create log file: $log_file");
         chmod($log_file, 0666);
     }

     // Load environment variables
     $dotenv = Dotenv::createImmutable('C:\xampp\htdocs\gebi1');
     try {
         $dotenv->load();
     } catch (Exception $e) {
         $error = "Failed to load .env file: " . $e->getMessage();
         error_log($error, 3, $log_file);
         die($error);
     }

     // Retrieve Twilio credentials
     $sid = $_ENV['TWILIO_SID'] ?? '';
     $token = $_ENV['TWILIO_TOKEN'] ?? '';
     $from_number = $_ENV['TWILIO_FROM_NUMBER'] ?? '';

     // Validate credentials
     if (!$sid || !$token || !$from_number) {
         $error = "Missing Twilio credentials: SID=" . ($sid ? 'set' : 'missing') . ", TOKEN=" . ($token ? 'set' : 'missing') . ", FROM_NUMBER=" . ($from_number ? 'set' : 'missing');
         error_log($error, 3, $log_file);
         die($error);
     }

     // Initialize Twilio client
     try {
         $client = new Client($sid, $token);
     } catch (Exception $e) {
         $error = "Twilio client initialization failed: " . $e->getMessage();
         error_log($error, 3, $log_file);
         die($error);
     }

     // Send test SMS
     $recipient_number = '+251916273479'; // Use the provided number
     try {
         $message = $client->messages->create(
             $recipient_number,
             [
                 'from' => $from_number,
                 'body' => 'Test SMS from Werabe Land Lease System'
             ]
         );
         $success = "Message sent: " . $message->sid;
         error_log($success, 3, $log_file);
         echo $success;
     } catch (Exception $e) {
         $error = "SMS send failed for $recipient_number: [HTTP " . $e->getCode() . "] " . $e->getMessage();
         error_log($error, 3, $log_file);
         echo $error;
     }
     ?>
     ```