<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\Actions\SubscriptionAccess;
use ZeroAd\WP\IncludedContent;
use ZeroAd\WP\Renderer;

class SubscriptionAccessTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
        $GLOBALS["__posts"][10] = (object) [
            "ID" => 10, "post_type" => "post", "post_status" => "publish", "post_password" => ""
        ];
    }

    private function selectPost(): void
    {
        $_POST = ["zeroad_freedom_nonce" => "valid", "zeroad_freedom_included" => "1"];
        IncludedContent::save(10);
    }

    public function testSelectionRequiresAnEditorAndValidNonce(): void
    {
        $GLOBALS["__can_edit_post"] = false;
        $this->selectPost();
        $this->assertFalse(IncludedContent::contains(get_post(10)));
        $GLOBALS["__can_edit_post"] = true;
        $GLOBALS["__valid_nonce"] = false;
        $this->selectPost();
        $this->assertFalse(IncludedContent::contains(get_post(10)));
    }

    public function testExplicitSelectionCanBeRemovedButAutosaveLeavesItAlone(): void
    {
        $this->selectPost();
        $this->assertTrue(IncludedContent::contains(get_post(10)));
        $_POST = ["zeroad_freedom_nonce" => "valid"];
        $GLOBALS["__is_autosave"] = true;
        IncludedContent::save(10);
        $this->assertTrue(IncludedContent::contains(get_post(10)));
        $GLOBALS["__is_autosave"] = false;
        IncludedContent::save(10);
        $this->assertFalse(IncludedContent::contains(get_post(10)));
    }

    public function testAccessNeedsBothMembershipAndSelectedContent(): void
    {
        $this->selectPost();
        SubscriptionAccess::registerPluginOverrides(Renderer::SUBSCRIBER_CONTEXT);
        $post = get_post(10);
        $GLOBALS["__posts"][11] = clone $post;
        $GLOBALS["__posts"][11]->ID = 11;
        $this->assertFalse(apply_filters("pmpro_has_membership_access_filter", false, $post));
        $GLOBALS["zeroad_token_context"] = Renderer::SUBSCRIBER_CONTEXT;
        $this->assertTrue(apply_filters("pmpro_has_membership_access_filter", false, $post));
        $this->assertFalse(apply_filters("wpmem_block", true, ["post_id" => 10]));
        $this->assertTrue(apply_filters("wpmem_block", true, ["post_id" => 11]));
        $this->assertFalse(apply_filters("pmpro_has_membership_access_filter", false, get_post(11)));
        $this->assertTrue(apply_filters("wpmem_block", true));
        $this->assertTrue(apply_filters("pmpro_has_membership_access_filter", true, null));
        $this->assertFalse(apply_filters("pmpro_has_membership_access_filter", false, null));
        $this->assertArrayNotHasKey("post_password_required", $GLOBALS["__wp_hooks"]);
        $this->assertArrayNotHasKey("woocommerce_customer_has_subscription", $GLOBALS["__wp_hooks"]);
    }

    public function testPrivateDraftPasswordAndCommerceRestrictionsSurviveSelection(): void
    {
        $this->selectPost();
        $GLOBALS["zeroad_token_context"] = Renderer::SUBSCRIBER_CONTEXT;
        $post = get_post(10);
        foreach (["private", "draft"] as $status) {
            $post->post_status = $status;
            $this->assertFalse(SubscriptionAccess::allowsPost($post));
        }
        $post->post_status = "publish";
        $post->post_password = "secret";
        $this->assertFalse(SubscriptionAccess::allowsPost($post));
        $post->post_password = "";
        $post->post_type = "product";
        $this->assertFalse(SubscriptionAccess::allowsPost($post));
    }

    public function testAccessDoesNotApplyToAdministrativeOrMachineRequests(): void
    {
        $this->selectPost();
        $GLOBALS["zeroad_token_context"] = Renderer::SUBSCRIBER_CONTEXT;
        foreach (["__is_admin", "__is_ajax", "__is_json"] as $flag) {
            $GLOBALS[$flag] = true;
            $this->assertFalse(SubscriptionAccess::allowsPost(get_post(10)));
            $GLOBALS[$flag] = false;
        }
    }
}
