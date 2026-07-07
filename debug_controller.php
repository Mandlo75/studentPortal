<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/student/dashboard', 'GET', ['id' => 'N02423145F']);
$response = $kernel->handle($request);
$content = $response->getContent();
echo 'Status: ' . $response->getStatusCode() . "\n";
if (strpos($content, '<table') !== false) {
    echo "Table found\n";
}
if (strpos($content, 'No financial history records recorded on this account index.') !== false) {
    echo "No data message found\n";
}
$rows = substr_count($content, '<tr class="hover:bg-slate-50/50 transition duration-150">');
echo "Row template found: $rows\n";
if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $content, $tbody)) {
    $snippet = trim(substr($tbody[1], 0, 400));
    echo "TBODY snippet:\n" . $snippet . "\n";
}
$kernel->terminate($request, $response);
