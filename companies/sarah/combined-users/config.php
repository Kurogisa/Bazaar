<?php
/**
 * Project configuration
 * Update this file for each company (A, B, C).
 */

return [
  // Change this per company, for example: "Company A", "Company B", "Company C"
  'company_name' => 'Company A',

  // Local MySQL database settings (this company only)
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'company_a_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
  ],

  // Partner API endpoints (remote companies)
  // Keep these configurable in one place.
  'remote_api_urls' => [
    'http://company-b.com/api/users.php',
    'http://company-c.com/api/users.php',
  ],

  // CURL timeout in seconds
  'curl_timeout' => 5,
];
