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

    print_r($data);

    // Extract user info
    $googleId = $user->getId();
    $email = $data['email'] ?? null;

    // 4. Find the Account in the Database
    $accounts = AuthorizedAccount::findBy('provider_user_id', $googleId);

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
    // 1. Create a provider
    $provider = new League\OAuth2\Client\Provider\Facebook([
    'clientId'     => $GLOBALS['client_id_facebook'],
    'clientSecret' => $GLOBALS['client_secret_facebook'],
    'graphApiVersion' => 'v23.0',
    'redirectUri'  => 'http://localhost:5173/login',
    ]);


    // 2. Redirect user to Facebook login
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
    $googleId = $user->getId();
    $email = $data['email'] ?? null;

    // 4. Find the Account in the Database
    $accounts = AuthorizedAccount::findBy('provider_user_id', $googleId);
    $barangay = $accounts->getBarangay();

    if(!$accounts) {
        returnError("This Facebook Account is not Authorized. Please contact the developer if you want to be authorized. Thank you :>", 400);
    }

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