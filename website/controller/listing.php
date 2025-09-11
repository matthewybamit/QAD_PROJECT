<?php
// controller/listing.php
require_once 'models/SchoolQuery.php';

$schoolQuery = new SchoolQuery($db);
$result = $schoolQuery->getSchools([
    'search' => $_GET['search'] ?? '',
    'limit'  => (int)($_GET['limit'] ?? 10),
    'page'   => (int)($_GET['page'] ?? 1),
    'sort'   => $_GET['sort'] ?? 'school_name',
    'order'  => $_GET['order'] ?? 'asc'
]);

$schools = $result['schools'];
$totalRecords = $result['totalRecords'];
$totalPages = $result['totalPages'];
// ... other variables

// Include the view
require 'views/listing.view.php';