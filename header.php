
<!DOCTYPE html>
<html>

<head>
   <title><?php echo htmlspecialchars($appName); ?></title>
   <link rel="stylesheet" type="text/css" href="assets/css/style.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
   <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
   <meta name="screen-orientation" content="portrait">
   <meta name="x5-orientation" content="portrait">
   <meta name="apple-mobile-web-app-title" content="SYNOCR DB">
   <meta name="apple-mobile-web-app-capable" content="yes">
   <link rel="apple-touch-icon" href="assets/180x180.png" type="image/png" />
   <link rel="icon" href="assets/192x192.png" type="image/png" />
   <link rel="fav-icon" href="assets/48x48.png" type="image/png" />
   <link rel="manifest" href="manifest.php">
</head>

<body>
<div class="full-container">
   <header class="header" id="header">
      <nav class="navbar container">
         <a href="main.php" class="brand"><?php echo htmlspecialchars($appName); ?></a>
         <div class="search">
            <form id="search-form" class="search-form" method="get" action="search.php">
               <input type="text" name="query" class="search-input" placeholder="Dokumente durchsuchen...">
               <button type="submit" class="search-submit"><i class="bx bx-search"></i></button>
            </form>
         </div>
         <div class="menu" id="menu">
            <ul class="menu-inner">
               <li class="menu-item"><a href="documents.php" class="menu-link">Dokumente</a></li>
               <li class="menu-item"><a href="edit_tags.php" class="menu-link">Tags</a></li>
               <li class="menu-item"><a href="<?php echo htmlspecialchars($editUserLink); ?>" class="menu-link">Einstellungen</a></li>
               <li class="menu-item"><a href="logout.php" class="menu-link">Abmelden</a></li>
            </ul>
         </div>
         <div class="burger" id="burger">
            <span class="burger-line"></span>
            <span class="burger-line"></span>
            <span class="burger-line"></span>
         </div>
      </nav>
   </header>

   <div class="content">