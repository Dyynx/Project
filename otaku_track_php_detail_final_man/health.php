<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/services/AniListService.php';
require_once __DIR__ . '/app/services/JikanService.php';
$api = new JikanService();
$ani = new AniListService();
$phpOk = PHP_VERSION;
$curlOk = extension_loaded('curl');
$dbOk = false; $dbError = '';
try { db()->query('SELECT 1'); $dbOk = true; } catch (Throwable $e) { $dbError = $e->getMessage(); }
$sample = $api->popularAnime(3);
$apiOk = count($sample) > 0;
$apiError = $api->lastError();
$aniSample = $ani->popularAnime(2);
$aniOk = count($aniSample) > 0;
$aniError = $ani->lastError();
$caBundle = defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE) ? API_CA_BUNDLE : 'CA bundle tidak ditemukan';
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Otaku Track Health</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-dark text-light"><main class="container py-5"><h1>Otaku Track — Health Check</h1><p class="text-secondary">Halaman ini dipakai untuk memastikan PHP, MySQL, cURL, dan Jikan benar-benar tersambung.</p><div class="card bg-black border-secondary"><div class="card-body"><p>PHP: <b class="text-success">OK</b> <?=htmlspecialchars($phpOk)?></p><p>cURL: <b class="<?=$curlOk?'text-success':'text-danger'?>"><?=$curlOk?'OK':'FAIL'?></b></p><p>CA bundle: <b class="<?=is_file($caBundle)?'text-success':'text-danger'?>"><?=htmlspecialchars(is_file($caBundle)?'OK':'FAIL')?></b></p><p>MySQL: <b class="<?=$dbOk?'text-success':'text-danger'?>"><?=$dbOk?'OK':'FAIL'?></b> <?=$dbError?htmlspecialchars($dbError):''?></p><p>Jikan API: <b class="<?=$apiOk?'text-success':'text-danger'?>"><?=$apiOk?'OK':'FAIL'?></b></p><?php if($apiError): ?><pre class="text-warning mb-0"><?=htmlspecialchars($apiError)?></pre><?php endif; ?><?php if($apiOk): ?><hr><p class="mb-2">Jikan sample:</p><ul><?php foreach($sample as $a): ?><li><?=htmlspecialchars($a['title']??'Untitled')?> — <?=htmlspecialchars((string)($a['score']??'N/A'))?></li><?php endforeach; ?></ul><?php endif; ?><p>AniList: <b class="<?=$aniOk?'text-success':'text-danger'?>"><?=$aniOk?'OK':'FAIL'?></b></p><p class="small text-secondary">CA path: <?=htmlspecialchars((string)$caBundle)?></p><?php if($aniError): ?><pre class="text-warning mb-0"><?=htmlspecialchars($aniError)?></pre><?php endif; ?><?php if($aniOk): ?><p class="mb-0">AniList fallback berhasil mengembalikan <?=$aniSample ? count($aniSample) : 0?> judul.</p><?php endif; ?></div></div><a class="btn btn-primary mt-4" href="<?=htmlspecialchars(url('home'))?>">Kembali ke Home</a></main></body></html>