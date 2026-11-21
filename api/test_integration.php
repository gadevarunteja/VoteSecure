
// ----------------------------------------------------------------------
// Test 2: Admin Login
// ----------------------------------------------------------------------

$adminEmail = getenv('VOTESECURE_TEST_ADMIN_EMAIL');
$adminPassword = getenv('VOTESECURE_TEST_ADMIN_PASSWORD');

if (!$adminEmail || !$adminPassword) {
    die("ERROR: Configure VOTESECURE_TEST_ADMIN_EMAIL and VOTESECURE_TEST_ADMIN_PASSWORD before running tests.\n");
}

$adminParams = [
    'username' => $adminEmail,
    'password' => $adminPassword
];

$res = httpRequest("$baseUrl/api/auth/login.php", 'POST', $adminParams, $cookieFileAdmin);

assertTest(
    "Admin Login works with configured credentials",
    isset($res['json']['success']) &&
    $res['json']['success'] === true &&
    $res['json']['user']['role'] === 'admin',
    $res['body']
);
