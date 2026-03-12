<?php
if (!isset($currentPage)) { $currentPage = ""; }
$basePath = isset($basePath) ? (string)$basePath : "";
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>index.php">Noirium</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "home") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "about") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "product") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>product.php">Products</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "news") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>news.php">News</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "secure") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>secure/user.php">Secure</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "lab") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>lab.php">Lab</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "contact") ? "active" : ""; ?>" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>contact.php">Contact</a>
        </li>
      </ul>
    </div>
  </div>
</nav>