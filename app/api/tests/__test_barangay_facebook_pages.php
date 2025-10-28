<?php
require_once __DIR__ . '/../../models/BarangayFacebookPages.php';
require_once __DIR__ . '/../../models/Barangay.php';

echo "<h1>Testing Barangay Facebook Pages API Endpoint\n <h1>";

// CRUD operations for Barangay Facebook Pages can be tested here.

// Insert
echo "<h2>Inserting a New Barangay Facebook Page</h2>";
$page = new BarangayFacebookPages();
$page->setBarangayId(1);
$page->setPageId('1234567890');
$page->setPageName('Test Page Name');

if($page->insert()) {
    echo "Inserted Barangay Facebook Page with ID: " . $page->getId() . "\n";
    print_r($page->getAssoc());
}

// Update 
echo "<h2>Updating the Barangay Facebook Page</h2>";
$page->setPageName('Updated Page Name');
if($page->update()) {
    echo "Updated Barangay Facebook Page:\n";
    print_r($page->getAssoc());
}

// Delete
echo "<h2>Deleting the Barangay Facebook Page</h2>";
if($page->delete()) {
    echo "Deleted Barangay Facebook Page with ID: " . $page->getId() . "\n";
} else {
    echo "Failed to delete Barangay Facebook Page with ID: " . $page->getId() . "\n";
}


// Read 
echo "<h2>Fetching Barangay Facebook Page</h2>";
$pages = BarangayFacebookPages::all();
foreach($pages as $p) {
    print_r($p);
    echo "<br>";
}

echo "<h2>Fetching Barangay Facebook Page by Barangay</h2>";
$barangay = new Barangay(2);
$pagesByBarangayID = BarangayFacebookPages::all(false, false,$barangay);
foreach($pagesByBarangayID as $p) {
    print_r($p);
    echo "<br>";
}








