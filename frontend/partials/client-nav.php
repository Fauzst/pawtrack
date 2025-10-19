<!-- Navigation Bar -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">PawTrack</div>
        <ul class="nav-links">
            <li><a href="/about"
                    class="<?= strpos($_SERVER['REQUEST_URI'], 'about') !== false ? 'active' : '' ?>">About Us</a></li>

            <li><a href="/contact"
                    class="<?= strpos($_SERVER['REQUEST_URI'], 'contact') !== false ? 'active' : '' ?>">Contact Us</a></li>

            <li><a href="/dashboard"
                    class="<?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">Dashboard</a></li>

            <li><a href="/faqs"
                    class="<?= strpos($_SERVER['REQUEST_URI'], 'faqs') !== false ? 'active' : '' ?>">FAQs</a></li>
        </ul>
        <div class="user-icon">
            <i class="fa-solid fa-user"></i>
        </div>
    </div>
</nav>
