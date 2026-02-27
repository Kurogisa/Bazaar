<?php
if (!isset($currentPage)) { $currentPage = ""; }
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Noirium</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "home") ? "active" : ""; ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "about") ? "active" : ""; ?>" href="about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "product") ? "active" : ""; ?>" href="product.php">Products</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "news") ? "active" : ""; ?>" href="news.php">News</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($currentPage === "contact") ? "active" : ""; ?>" href="contact.php">Contact</a>
        </li>
      </ul>
    </div>
  </div>
</nav>