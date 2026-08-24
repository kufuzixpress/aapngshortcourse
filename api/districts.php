<?php
require_once __DIR__ . '/../includes/api.php';

$provinceId = (int)($_GET['province_id'] ?? 0);
if (!$provinceId) json_out(true, []);

$stmt = db()->prepare('SELECT id, name FROM districts WHERE province_id = ? AND is_active = 1 ORDER BY name');
$stmt->execute([$provinceId]);
json_out(true, $stmt->fetchAll());
