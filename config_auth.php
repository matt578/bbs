<?php
declare(strict_types=1);

define('APP_URL', 'http://localhost/bohol_bicycle_inventory');

define('FB_APP_ID', 'YOUR_FACEBOOK_APP_ID');
define('FB_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET');
define('FB_REDIRECT_URI', APP_URL . '/facebook_callback.php');

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-real-email@gmail.com');
define('MAIL_PASSWORD', 'your-gmail-app-password');
define('MAIL_FROM_EMAIL', 'your-real-email@gmail.com');
define('MAIL_FROM_NAME', 'Bohol Bicycle Inventory');

define('VERIFICATION_CODE_EXPIRY_MINUTES', 10);