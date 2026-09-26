<?php

if (!defined("ABSPATH")) {
    exit();
}

$pages = [
    "zeroad-config" => __("Settings", "zero-ad-network"),
    "zeroad-cache-config" => __("Page cache setup", "zero-ad-network"),
    "zeroad-about" => __("About & earnings", "zero-ad-network"),
];
?>
<nav class="nav-tab-wrapper zeroad-tabs" aria-label="<?php esc_attr_e("Zero Ad Network pages", "zero-ad-network"); ?>">
    <?php foreach ($pages as $slug => $label): ?>
        <a class="<?php echo $activePage === $slug ? "nav-tab nav-tab-active" : "nav-tab"; ?>" href="<?php echo esc_url(admin_url("admin.php?page=" . $slug)); ?>" <?php if ($activePage === $slug): ?>aria-current="page"<?php endif; ?>><?php echo esc_html($label); ?></a>
    <?php endforeach; ?>
</nav>
