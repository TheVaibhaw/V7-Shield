<?php defined('APP_ROOT') || exit; /** @var array $config @var string $pageTitle @var string $pageKey */ ?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e($config['name']) ?></title>
<meta name="description" content="<?= e($config['name']) ?> — <?= e($config['tagline']) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/v7-shield-logo.svg')) ?>">
<script>try{var t=localStorage.getItem('v7-theme');if(t)document.documentElement.dataset.theme=t;if(localStorage.getItem('v7-sidebar')==='collapsed')document.documentElement.classList.add('sb-collapsed');}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/dashboard.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
</head>
<body data-page="<?= e($pageKey) ?>" data-csrf="<?= e(csrf_token()) ?>" data-api="<?= e(url('api.php')) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<div class="app">
