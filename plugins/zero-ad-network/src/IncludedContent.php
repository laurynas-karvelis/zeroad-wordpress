<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
    exit();
}

class IncludedContent
{
    private const META_KEY = "_zeroad_freedom_included";

    public static function register(): void
    {
        add_action("add_meta_boxes", [self::class, "addMetaBox"]);
        add_action("save_post", [self::class, "save"]);
    }

    public static function addMetaBox(): void
    {
        add_meta_box(
            "zeroad-freedom-access",
            __("Freedom access", "zero-ad-network"),
            [self::class, "render"],
            ["post", "page"],
            "side"
        );
    }

    public static function render($post): void
    {
        wp_nonce_field("zeroad_freedom_access_" . $post->ID, "zeroad_freedom_nonce");
        ?>
        <label>
            <input type="checkbox" name="zeroad_freedom_included" value="1"
                <?php checked(get_post_meta($post->ID, self::META_KEY, true), "1"); ?>>
            <?php esc_html_e("Include this content with Freedom", "zero-ad-network"); ?>
        </label>
        <p class="description"><?php esc_html_e(
            "Grants reading access through Paid Memberships Pro or WP-Members. Other paywalls need a custom integration. Private, draft, and password-protected content stays protected. Clear page caches after changing access.",
            "zero-ad-network"
        ); ?></p>
        <?php
    }

    public static function save(int $postId): void
    {
        $nonce = isset($_POST["zeroad_freedom_nonce"]) && is_string($_POST["zeroad_freedom_nonce"])
            ? sanitize_text_field(wp_unslash($_POST["zeroad_freedom_nonce"]))
            : "";

        if ($nonce === "" || !wp_verify_nonce($nonce, "zeroad_freedom_access_" . $postId)) {
            return;
        }

        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || !current_user_can("edit_post", $postId)) {
            return;
        }

        if (!in_array(get_post_type($postId), ["post", "page"], true)) {
            return;
        }

        $included = isset($_POST["zeroad_freedom_included"]) && is_string($_POST["zeroad_freedom_included"])
            ? sanitize_text_field(wp_unslash($_POST["zeroad_freedom_included"]))
            : "";

        if ($included === "1") {
            update_post_meta($postId, self::META_KEY, "1");
        } else {
            delete_post_meta($postId, self::META_KEY);
        }
    }

    public static function contains($post): bool
    {
        if (!$post || $post->post_status !== "publish" || $post->post_password !== "") {
            return false;
        }

        if (!in_array($post->post_type, ["post", "page"], true)) {
            return false;
        }

        $included = get_post_meta($post->ID, self::META_KEY, true) === "1";

        return (bool) apply_filters("zeroad_freedom_includes_post", $included, $post);
    }
}
