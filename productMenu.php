<?php
// Old URL kept so existing links still work: the product list now lives in market.php.
$map = ['fruit' => 'Fruit', 'vegetable' => 'Vegetable', 'grain' => 'Grains'];
$type = isset($_GET['type']) && is_string($_GET['type']) ? strtolower($_GET['type']) : '';
header('Location: market.php' . (isset($map[$type]) ? '?cat=' . $map[$type] : ''), true, 301);
exit;
