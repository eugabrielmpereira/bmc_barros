<?php
/**
 * <head> padrão da loja BMC.
 *
 * Variáveis opcionais: $pageTitle, $pageDescription, $canonicalPath, $ogImageUrl, $ogType, $jsonLd (array|list), $noindex
 */
require_once __DIR__ . '/icons.php';

$client = $client ?? client_config();
$storeName = (string) ($client['name'] ?? 'Loja');
$storeLegalName = (string) ($client['legalName'] ?? $storeName);
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' . $storeName : $storeLegalName;
$pageDescription = trim((string) ($pageDescription ?? ($client['seoDescription'] ?? '')));
$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$canonicalUrl = app_url((string) ($canonicalPath ?? $requestPath));
$ogImageUrl = (string) ($ogImageUrl ?? app_url((string) ($client['assets']['ogImage'] ?? '')));
$ogType = (string) ($ogType ?? 'website');
$noindex = (bool) ($noindex ?? false);
$fontsUrl = (string) ($client['fontsUrl'] ?? '');

$GLOBALS['__commerce_css_in_head'] = true;
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= e($pageTitle) ?></title>
<?php if ($pageDescription !== ''): ?>
<meta name="description" content="<?= e($pageDescription) ?>" />
<?php endif; ?>
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow" />
<?php else: ?>
<link rel="canonical" href="<?= e($canonicalUrl) ?>" />
<?php endif; ?>
<meta name="theme-color" content="#17357f" />
<meta property="og:site_name" content="<?= e($storeLegalName) ?>" />
<meta property="og:locale" content="pt_BR" />
<meta property="og:type" content="<?= e($ogType) ?>" />
<meta property="og:title" content="<?= e($pageTitle) ?>" />
<?php if ($pageDescription !== ''): ?>
<meta property="og:description" content="<?= e($pageDescription) ?>" />
<?php endif; ?>
<meta property="og:url" content="<?= e($canonicalUrl) ?>" />
<?php if ($ogImageUrl !== ''): ?>
<meta property="og:image" content="<?= e($ogImageUrl) ?>" />
<meta name="twitter:card" content="summary_large_image" />
<?php endif; ?>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(app_url((string) ($client['assets']['favicon'] ?? ''))) ?>" />
<link rel="apple-touch-icon" href="<?= e(app_url((string) ($client['assets']['appleIcon'] ?? ''))) ?>" />
<?php if (!empty($client['fontPreload'])): ?>
<link rel="preload" href="<?= e(app_url((string) $client['fontPreload'])) ?>" as="font" type="font/woff2" crossorigin />
<?php endif; ?>
<?php if ($fontsUrl !== ''): ?>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="<?= e($fontsUrl) ?>" />
<?php endif; ?>
<link rel="stylesheet" href="<?= shared_commerce_asset('commerce.css') ?>" />
<link rel="stylesheet" href="<?= client_asset('css/style.css') ?>?v=2" />
<?php
$jsonLdBlocks = [];
if (!empty($jsonLd) && is_array($jsonLd)) {
    $jsonLdBlocks = array_is_list($jsonLd) ? $jsonLd : [$jsonLd];
}
foreach ($jsonLdBlocks as $block): ?>
<script type="application/ld+json"><?= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
