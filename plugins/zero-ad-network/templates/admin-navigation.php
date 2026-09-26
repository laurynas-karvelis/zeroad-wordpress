<?php

if (!defined("ABSPATH")) {
    exit();
}

$zeroadPages = [
    "zeroad-config" => __("Settings", "zero-ad-network"),
    "zeroad-cache-config" => __("Page cache setup", "zero-ad-network"),
    "zeroad-about" => __("About & earnings", "zero-ad-network"),
];
?>
<nav class="nav-tab-wrapper zeroad-tabs" aria-label="<?php esc_attr_e("Zero Ad Network pages", "zero-ad-network"); ?>">
    <?php foreach ($zeroadPages as $zeroadSlug => $zeroadLabel): ?>
        <a class="<?php echo $zeroadActivePage === $zeroadSlug ? "nav-tab nav-tab-active" : "nav-tab"; ?>" href="<?php echo esc_url(admin_url("admin.php?page=" . $zeroadSlug)); ?>" <?php if ($zeroadActivePage === $zeroadSlug): ?>aria-current="page"<?php endif; ?>><?php echo esc_html($zeroadLabel); ?></a>
    <?php endforeach; ?>
</nav>
