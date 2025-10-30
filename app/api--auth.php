<?php
declare(strict_types=1);
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

require_once('../vendor/autoload.php');

// Models Imports
require_once __DIR__ . '/models/SkOfficial.php';
require_once __DIR__ . '/models/Barangay.php';
require_once __DIR__ . '/models/AuthorizedAccount.php';
require_once __DIR__ . '/models/PasswordResets.php';
require_once __DIR__ . '/models/BarangayFacebookPages.php';
require_once __DIR__ . '/models/FacebookPageTokens.php';
require_once __DIR__ . '/helpers/Mailer.php';

/** Check Guard Constant */
if (!defined('__BASE')) { exit(); }



/** Extract Action */
$action = $_GET['a'] ?? '';



// Authentication & Authorization API
if ($action === 'login')
{
    // Get Inputs
    $identifier = $_POST['identifier'] ?? '';
    $password   = $_POST['password']   ?? '';

    // validate inputs
    if (empty(trim($identifier)) || empty($password)) {
        returnError('Username/Email and password are required.');
    }

    // Try
    try {
        $barangay = Barangay::login($identifier, $password);
        require_once __DIR__ . '/models/Barangay.php';

        returnSuccess([
            'barangay' => $barangay->getAssoc(true),
        ]);
    }
    catch (Exception $e) {
        returnError($e->getMessage());
    }
}

else if ($action === 'authorized')
{
    $sk_official = null;
    if (isset($_COOKIE['jwt'])) {
        $jwt = $_COOKIE['jwt'];
        try {
            $decoded = JWT::decode($jwt, new Key($GLOBALS['secret_key'], 'HS256'));            // ✅ token is valid
            $barangay = Barangay::findBy('id', $decoded->barangayId);
        } catch (Exception $e) {
            // ❌ token is missing/invalid/expired
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(["error" => "No token provided"]);
        exit;
    }


    try {
        returnSuccess([
            'barangay' => $barangay->getAssoc(true),
        ]);
    }
    catch (Exception $e) {
        returnError($e->getMessage());
    }
}

else if($action === 'logout') {
    authorizeRequest(); // Ensure the user is authorized
    
    // Clear the JWT cookie
    setcookie('jwt', '', time() - 3600, '/', '', false, true); // Adjust path and domain as needed
    returnSuccess(['message' => 'Logged out successfully.']);
}






// Authorization using Third Party Accounts (Facebook and Google)
if ($action === 'process-google')
{
    // 1. Create a provider (Google example)
    $provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => $GLOBALS['client_id_google'],
    'clientSecret' => $GLOBALS['client_secret_google'],
    'redirectUri'  => 'http://localhost:5173/login',
    ]);


    // 2. Redirect user to Google login
    if(!isset($_POST["code"])) {
        $authUrl = $provider->getAuthorizationUrl();
        header('Location: ' . $authUrl);
        returnError('Invalid code received.', 400);
        exit;
    }

    // 3. Handle callback: exchange code for access token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_POST['code']
    ]);

    $user = $provider->getResourceOwner($token);
    $data = $user->toArray();

    // Extract user info
    $facebookId = $user->getId();
    $email = $data['email'] ?? null;

    // 4. Find the Account in the Database
    $accounts = AuthorizedAccount::findBy('provider_user_id', $facebookId);

    if(!$accounts) {
        returnError("This Google Account is not Authorized. Please contact the developer if you want to be authorized. Thank you :>", 400);
    }

    $barangay = $accounts->getBarangay();

    // 5. Issue your own JWT/cookie
    if($accounts) {
        $date   = new DateTimeImmutable();
        $expire_at = $date->modify('+4 week')->getTimestamp();
        $request_data = [
            'iss'  => 'localhost.youth',                    // Issuer
            'exp'  => $expire_at,                           // Expire
            'barangayId' => $accounts->getId(),
            'barangayName' => $barangay->getName(),  
            'barangayUsername' => $barangay->getUsername()                  
        ];

        // Create the Token
        $jwt = JWT::encode($request_data, $GLOBALS['secret_key'], 'HS256');      

        // Create and Set the JWT Cookie
        setcookie(
            "jwt",
            $jwt,
            [
                "path" => "/",
                // Set this to true in production
                "secure" => false,     // only HTTPS
                "httponly" => true,   // JavaScript can’t read it
                "samesite" => "Strict"
            ]
        );


        returnSuccess([
            'barangay' => $barangay->getAssoc(true),
        ]);
    }
}

// Authorization using Third Party Accounts (Facebook)
else if ($action === 'process-facebook')
{
    $appId = $GLOBALS['client_id_facebook'];
    $appSecret = $GLOBALS['client_secret_facebook'];

    // 1. Create a provider
    $provider = new League\OAuth2\Client\Provider\Facebook([
    'clientId'     => $appId,
    'clientSecret' => $appSecret,
    'graphApiVersion' => 'v24.0',
    'redirectUri'  => 'http://localhost:5173/login',
    ]);


    // 2. Redirect user to Facebook login
    if(!isset($_POST["code"])) {
        $authUrl = $provider->getAuthorizationUrl();
        header('Location: ' . $authUrl);
        returnError('Invalid code received.', 400);
        exit;
    }

    // 3. Handle callback: exchange code for short lived access token
    $shortLivedToken = $provider->getAccessToken('authorization_code', [
        'code' => $_POST['code']
    ]);

    // 4. Exchange for long-lived token and get expiry of the Token
    $exchangeUrl = "https://graph.facebook.com/v24.0/oauth/access_token?" .
    "grant_type=fb_exchange_token" .
    "&client_id={$appId}" .
    "&client_secret={$appSecret}" .
    "&fb_exchange_token={$shortLivedToken}";

    $response = file_get_contents($exchangeUrl);
    $longLivedToken = json_decode($response, true);
    $accessToken = $longLivedToken['access_token'];
    
    $expiryTokenUrl = "https://graph.facebook.com/v24.0/debug_token?" . "input_token={$longLivedToken['access_token']}&access_token={$appId}|{$appSecret}";
    $expiryResponse = file_get_contents($expiryTokenUrl);
    $expiry = json_decode($expiryResponse, true);

    $user = $provider->getResourceOwner($shortLivedToken);
    $data = $user->toArray();

    // 5. Find the Account in the Database
    $facebookId = $user->getId();
    $email = $data['email'] ?? null;
    $account = AuthorizedAccount::findBy('provider_user_id', $facebookId);

    if(!$account) {
        returnError("This Facebook Account is not Authorized. Please contact the developer if you want to be authorized. Thank you :>", 400);
    }

    // 6. Set the long-lived access token and Expiry
    $account->setAccessToken($accessToken);
    $account->setTokenExpiry(date('Y-m-d H:i:s' ,$expiry['data']['data_access_expires_at']));
    $account->update();
    

    // TEST 1: Get Pages
    $pagesUrl = "https://graph.facebook.com/v24.0/me/accounts?access_token=$accessToken";
    $pages = json_decode(file_get_contents($pagesUrl), true);

    // Todo:: Filter out facebook pages that is in you business portfolio

    
    // Find the Barangay Facebook Page in the Database (only assumming that the client only has one Facebook Page)
    $barangayFacebookPage = BarangayFacebookPages::findBy('barangay_id', $account->getBarangayId());


    if ($barangayFacebookPage) {
        // Compare the Page IDs
        if ($pages['data'][0]['id'] === $barangayFacebookPage->getPageId()) {
            // Page IDs match

            // Find the the page access token that is under the barangay_page and authorized_account
            $facebookPageToken = FacebookPageTokens::findByComposite([
                'authorized_account_id' => $account->getId(),
                'barangay_facebook_page_id' => $barangayFacebookPage->getId()
            ]);

            $facebookPageToken->setPageAccessToken($pages['data'][0]['access_token']);
            $facebookPageToken->update();
        } else {
            // Page IDs do not match
            echo "Facebook Page ID does not match the stored Barangay Facebook Page ID.";
        }
    } else {
        
        // If no barangay facebook page found then 
        // Check if the facebook page is in business portfolio and a true facebook page of the barangay of the authorized account
        // Then create a row of that barangay_facebook_page of that barangay
    }
    
    // 7. Issue your own JWT/cookie
    $barangay = $account->getBarangay();
    if ($account) {
        $date = new DateTimeImmutable();
        $expire_at = $date->modify('+4 week')->getTimestamp();

        $request_data = [
            'iss' => 'localhost.youth',   // Issuer
            'exp' => $expire_at,          // Expiration
            'barangayId' => $barangay->getId(),
            'barangayName' => $barangay->getName(),
            'barangayUsername' => $barangay->getUsername(),
            'provider' => 'facebook',     // Optional: if you also support Google
            'fb_user_id' => $facebookId   // 👈 Add this field
        ];

        $jwt = JWT::encode($request_data, $GLOBALS['secret_key'], 'HS256');

        setcookie("jwt", $jwt, [
            "path" => "/",
            "secure" => false,     // set true in production (HTTPS)
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        returnSuccess([
            'barangay' => $barangay->getAssoc()
        ]);
    }
}


// Methods for Password Reset Feature
if($action === 'send-reset-otp')
{
    $email = $_POST['email'] ?? '';

    // Validate inputs
    if($email === '') {
        return ("Email is required.");
    }

    // Find the Account in the Database
    $accounts = AuthorizedAccount::findBy('email', $email);
    if(!$accounts) {
        returnError("No account found with that email address.", 400);
    }

    // Generate OTP
    $otp = createOtp(6);

    // ✅ Step 1: Mark all previous OTPs as used
    PasswordResets::markOldOtpsAsUsed($accounts->getId());

    // Store the OTP on the Database
    $passwordReset = new PasswordResets();
    $passwordReset->setAuthorizedAccountId($accounts->getId());
    $passwordReset->setOtp($otp);
    $passwordReset->setExpiresAt((new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s'));
    $passwordReset->insert();

    // Send to the Designated Email Address
    $mailer = new Mailer($accounts->getEmail(), 'Account Verification', "Welcome! Your OTP is: <b>$otp</b>. It will expire in 15 minutes. If you did not request this, please ignore this email.");
    $mailer->send();
        
    returnSuccess([
        'message' => 'OTP has been sent to your email address.'
    ]);
}

if($action === 'verify-reset-otp') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';

    if ($email === '' || $otp === '') {
        returnError("Email and OTP are required.", 400);
    }

    // Find account by email
    $account = AuthorizedAccount::findBy('email', $email);
    if (!$account) {
        returnError("No account found with that email address.", 400);
    }

    // Find the OTP belonging to this account and not yet used
    $passwordReset = PasswordResets::findActiveOtp($account->getId(), $otp);
    if (!$passwordReset) {
        returnError("Invalid or already used OTP.", 400);
    }

    // Check expiration
    $now = new DateTime();
    $expiresAt = new DateTime($passwordReset->getExpiresAt());
    if ($now > $expiresAt) {
        returnError("OTP has expired.", 400);
    }

    // ✅ Mark OTP as used immediately (for one-time use)
    $passwordReset->setUsed(1);
    $passwordReset->update();

    $payload = [
    'authorized_account_id' => $account->getId(),
    'exp' => time() + (10 * 60) // 10 minutes expiry
    ];

    $token = JWT::encode($payload, $GLOBALS['secret_key'], 'HS256');

    returnSuccess([
        'message' => 'OTP verified successfully.',
        'reset_token' => $token
    ]);
}

if ($action === 'reset-password') {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] 
        ?? ($_SERVER['HTTP_AUTHORIZATION'] 
        ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        returnError("Missing or invalid Authorization header", 401);
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key($GLOBALS['secret_key'], 'HS256'));
        $authorized_account_id = $decoded->authorized_account_id;
    } catch (Exception $e) {
        returnError("Invalid or expired token.", 401);
    }

    // Validate new password
    $newPassword = $_POST['new_password'] ?? '';

    // Hash password securely
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the Barangay's Password
    $account = AuthorizedAccount::findBy('id', $authorized_account_id);
    $barangay = $account->getBarangay();
    $barangay->setPassword($hashed);
    $barangay->update();


    // Login the Barangay (issue JWT)
    try {
        $barangay = Barangay::login($barangay->getUsername(), $newPassword);
        require_once __DIR__ . '/models/Barangay.php';

        returnSuccess([
            'barangay' => $barangay->getAssoc(true),
        ]);
    }
    catch (Exception $e) {
        returnError($e->getMessage());
    }

    returnSuccess(["message" => "Password reset successful."]);
}






// Helper function
function createOtp(int $length = 6): string {
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[random_int(0, strlen($digits) - 1)];
    }
    return $otp;
}