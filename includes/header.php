<?php
$basePath = ''; // ändra till din root
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'WCAG Tool' ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <h1 class="logo">WCAG Granskningsverktyg</h1>

        <nav class="main-nav">
            <a href="<?= $basePath ?>../pages/list-rapporter.php">Mina rapporter</a>
            <a href="<?= $basePath ?>../pages/create-rapport.php">Ny rapport</a>
        </nav>
    </div>
</header>
<main class="container main-content">