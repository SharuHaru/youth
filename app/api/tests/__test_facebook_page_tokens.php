<?php
require_once __DIR__ . '/../../models/FacebookPageTokens.php';
require_once __DIR__ . '/../../models/AuthorizedAccount.php';

echo "<h1>Testing Facebook Page Tokens API Endpoint\n <h1>";

// Insert
echo "<h2>Inserting a New Facebook Page Token</h2>";
$facebookPageToken = new FacebookPageTokens();
$facebookPageToken->setAuthorizedAccountId(1);
$facebookPageToken->setBarangayFacebookPageId(24);
$facebookPageToken->setPageAccessToken('EAAGm0PX4ZCpsBAKZCZA...');

if($facebookPageToken->insert()) {
    echo "Inserted Facebook Page Token with ID: " . $facebookPageToken->getId() . "\n";
    print_r($facebookPageToken->getAssoc());
}


//Update 
echo "<h2>Updating the Facebook Page Token</h2>";
$facebookPageToken->setPageAccessToken('EAAGm0PX4ZCpsBAKZCZA...UPDATED');
if($facebookPageToken->update()) {
    echo "Updated Facebook Page Token:\n";
    print_r($facebookPageToken->getAssoc());
}   

// Delete
echo "<h2>Deleting the Facebook Page Token</h2>";   
if($facebookPageToken->delete()) {
    echo "Deleted Facebook Page Token with ID: " . $facebookPageToken->getId() . "\n";
} else {
    echo "Failed to delete Facebook Page Token with ID: " . $facebookPageToken->getId() . "\n";
}

// Read
echo "<h2>Fetching All the Facebook Page Token</h2>";
$tokens = FacebookPageTokens::all();
foreach($tokens as $token) {
    print_r($token);
    echo "<br>";
}

echo "<h2>Fetching All the Facebook Page Token from one Authorized Account</h2>";
$tokens = FacebookPageTokens::all(false, false,new AuthorizedAccount(3));
foreach($tokens as $token) {
    print_r($token);
    echo "<br>";
}

