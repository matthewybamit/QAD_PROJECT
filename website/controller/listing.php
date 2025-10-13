<?php
// controller/listing.php
require_once 'models/SchoolQuery.php';

$schoolQuery = new SchoolQuery($pdo);

// Get schools list
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
$page = $result['currentPage'];
$limit = $result['limit'];
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'school_name';
$order = $_GET['order'] ?? 'asc';

// Get division statistics
$divisionStats = $schoolQuery->getSchoolCountByDivision();

// Include the view
require 'views/listing.view.php';