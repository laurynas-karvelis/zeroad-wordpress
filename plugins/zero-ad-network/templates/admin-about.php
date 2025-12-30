<?php

if (!defined("ABSPATH")) {
  exit();
} ?>

<div class="wrap zeroad-about">
    <h1><?php esc_html_e("About Zero Ad Network", "zero-ad-network"); ?></h1>

    <!-- Hero Section -->
    <div class="zeroad-hero">
        <h2 class="zeroad-mt-0"><?php esc_html_e("A Better Web for Everyone", "zero-ad-network"); ?></h2>
        <p style="font-size: 16px; line-height: 1.8; margin-bottom: 0;">
            <?php esc_html_e(
              "Zero Ad Network creates a fairer internet where users enjoy a cleaner browsing experience, and creators get paid fairly for quality content—without relying on intrusive ads or selling personal data.",
              "zero-ad-network"
            ); ?>
        </p>
    </div>

    <!-- The Problem -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("🚨 The Problem with Today's Web", "zero-ad-network"); ?></h2>
        
        <div class="zeroad-comparison-grid">
            <div class="zeroad-comparison-box negative">
                <h3>
                    <?php esc_html_e("For Users", "zero-ad-network"); ?>
                </h3>
                <ul style="line-height: 1.8;">
                    <li><?php esc_html_e("Bombarded with intrusive ads", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Personal data sold to third parties", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Cookie consent popups everywhere", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Multiple subscription paywalls", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Slow, cluttered websites", "zero-ad-network"); ?></li>
                </ul>
            </div>

            <div class="zeroad-comparison-box negative">
                <h3>
                    <?php esc_html_e("For Site Owners", "zero-ad-network"); ?>
                </h3>
                <ul style="line-height: 1.8;">
                    <li><?php esc_html_e("Rising ad blocker usage = lost revenue", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Declining ad rates year over year", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Pressure to use aggressive monetization", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Users frustrated with the experience", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Expensive to maintain quality content", "zero-ad-network"); ?></li>
                </ul>
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #dc3545; margin: 20px 0;">
            <p style="margin: 0; font-weight: 600; color: #666;">
                <?php esc_html_e(
                  "Result: A vicious cycle where users get a worse experience, ad blockers become more popular, and creators earn less—forcing even more aggressive monetization tactics.",
                  "zero-ad-network"
                ); ?>
            </p>
        </div>
    </div>

    <!-- The Solution -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("✅ The Zero Ad Network Solution", "zero-ad-network"); ?></h2>
        
        <p style="font-size: 16px; line-height: 1.8;">
            <?php esc_html_e(
              "Zero Ad Network breaks this cycle by introducing a subscription model where users pay a monthly fee to enjoy a cleaner web, and site owners earn revenue based on how much time subscribers spend on their content.",
              "zero-ad-network"
            ); ?>
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0;">
            <div class="zeroad-comparison-box positive">
                <h3>
                    <?php esc_html_e("For Users", "zero-ad-network"); ?>
                </h3>
                <ul style="line-height: 1.8; color: #155724;">
                    <li><?php esc_html_e("Ad-free browsing experience", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("No cookie consent banners", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Access to paywalled content", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("One subscription for all partner sites", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Privacy-first approach", "zero-ad-network"); ?></li>
                </ul>
            </div>

            <div class="zeroad-comparison-box positive">
                <h3>
                    <?php esc_html_e("For Site Owners", "zero-ad-network"); ?>
                </h3>
                <ul style="line-height: 1.8; color: #155724;">
                    <li><?php esc_html_e("New revenue stream from subscribers", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Paid based on content quality & engagement", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("No need for intrusive monetization", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Focus on creating great content", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Simple integration with existing site", "zero-ad-network"); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("🔄 How Zero Ad Network Works", "zero-ad-network"); ?></h2>

        <div style="background: white; border: 2px solid #0073aa; border-radius: 8px; padding: 30px; margin: 20px 0;">
            <ol style="font-size: 16px; line-height: 2; padding-left: 20px;">
                <li>
                    <strong><?php esc_html_e("User Subscribes", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "User chooses a plan: Clean Web ($6/mo), One Pass ($12/mo), or Freedom ($18/mo)",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("Extension Installed", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "User installs Zero Ad browser extension (Chrome, Firefox, or Edge)",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("Token Generated", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "Zero Ad creates a cryptographically signed token (ED25519) containing subscription details",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("User Visits Your Site", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "Browser extension sends token via X-Better-Web-Hello request header",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("Your Plugin Verifies Token", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "This WordPress plugin verifies the token signature using Zero Ad's public key",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("Features Enabled", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "Based on their plan, subscriber sees your site without ads/paywalls",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("Engagement Tracked", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "Extension anonymously tracks how long subscriber spends on your site",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
                
                <li>
                    <strong><?php esc_html_e("You Get Paid!", "zero-ad-network"); ?></strong><br>
                    <span class="description">
                        <?php esc_html_e(
                          "Monthly revenue distributed based on engagement time compared to all partner sites",
                          "zero-ad-network"
                        ); ?>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <!-- Revenue Model -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("💰 How You Earn Revenue", "zero-ad-network"); ?></h2>

        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 25px; margin: 20px 0;">
            <h3 style="margin-top: 0;"><?php esc_html_e(
              "Subscription Plans & Your Earnings",
              "zero-ad-network"
            ); ?></h3>
            
            <table class="widefat striped" style="background: white; margin: 15px 0;">
                <thead>
                    <tr>
                        <th><?php esc_html_e("Plan", "zero-ad-network"); ?></th>
                        <th><?php esc_html_e("User Pays", "zero-ad-network"); ?></th>
                        <th><?php esc_html_e("You Earn (Per Subscriber)", "zero-ad-network"); ?></th>
                        <th><?php esc_html_e("What You Provide", "zero-ad-network"); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e("Clean Web", "zero-ad-network"); ?></strong></td>
                        <td>$6/month</td>
                        <td style="color: #28a745; font-weight: 600;">$6/month*</td>
                        <td><?php esc_html_e("No ads, no cookie banners, no popups", "zero-ad-network"); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e("One Pass", "zero-ad-network"); ?></strong></td>
                        <td>$12/month</td>
                        <td style="color: #28a745; font-weight: 600;">$12/month*</td>
                        <td><?php esc_html_e("Free access to paywalled content", "zero-ad-network"); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e("Freedom", "zero-ad-network"); ?></strong></td>
                        <td>$18/month</td>
                        <td style="color: #28a745; font-weight: 600;">$18/month*</td>
                        <td><?php esc_html_e("Everything: ad-free + paywall access", "zero-ad-network"); ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="font-size: 13px; color: #666; margin-top: 15px;">
                <strong>*</strong> <?php esc_html_e(
                  "Revenue is distributed based on engagement time. If a subscriber spends 100% of their browsing time on partner sites at your site, you receive the full amount. If they spend 50% of their time on your site, you receive 50% of the amount.",
                  "zero-ad-network"
                ); ?>
            </p>
        </div>

        <div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 25px; margin: 20px 0;">
            <h4 style="margin-top: 0;"><?php esc_html_e("📊 Real Example", "zero-ad-network"); ?></h4>
            <p style="line-height: 1.8;">
                <strong><?php esc_html_e("Scenario:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e("You have both Clean Web and One Pass features enabled.", "zero-ad-network"); ?><br>
                <?php esc_html_e(
                  "1,000 Freedom plan subscribers visit partner sites this month.",
                  "zero-ad-network"
                ); ?><br>
                <?php esc_html_e("They spend a combined 10,000 hours on all partner sites.", "zero-ad-network"); ?><br>
                <?php esc_html_e("They spend 1,000 hours (10%) on YOUR site.", "zero-ad-network"); ?>
            </p>
            <p style="line-height: 1.8;">
                <strong><?php esc_html_e("Your Revenue:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e("Clean Web pool: 1,000 subscribers × $6 = $6,000", "zero-ad-network"); ?><br>
                <?php esc_html_e("Your share (10%): $600", "zero-ad-network"); ?><br><br>
                <?php esc_html_e("One Pass pool: 1,000 subscribers × $12 = $12,000", "zero-ad-network"); ?><br>
                <?php esc_html_e("Your share (10%): $1,200", "zero-ad-network"); ?><br><br>
                <span style="font-size: 20px; color: #28a745; font-weight: bold;">
                    <?php esc_html_e("Total Monthly Revenue: $1,800", "zero-ad-network"); ?>
                </span>
            </p>
        </div>
    </div>

    <!-- Privacy & Security -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("🔒 Privacy & Security", "zero-ad-network"); ?></h2>

        <div style="background: white; border: 2px solid #6c757d; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin-top: 0;"><?php esc_html_e("Cryptographic Token Verification", "zero-ad-network"); ?></h3>
            <p style="line-height: 1.8;">
                <?php esc_html_e(
                  "Every subscriber receives a cryptographically signed token using the ED25519 algorithm. This ensures:",
                  "zero-ad-network"
                ); ?>
            </p>
            <ul style="line-height: 1.8;">
                <li><?php esc_html_e("Tokens cannot be forged or tampered with", "zero-ad-network"); ?></li>
                <li><?php esc_html_e(
                  "Your plugin verifies authenticity using Zero Ad's public key",
                  "zero-ad-network"
                ); ?></li>
                <li><?php esc_html_e("Only valid, paying subscribers get the benefits", "zero-ad-network"); ?></li>
                <li><?php esc_html_e("Tokens automatically expire when subscription ends", "zero-ad-network"); ?></li>
            </ul>

            <h3><?php esc_html_e("No Personal Data in Tokens", "zero-ad-network"); ?></h3>
            <p style="line-height: 1.8;">
                <?php esc_html_e("Subscriber tokens contain only:", "zero-ad-network"); ?>
            </p>
            <ul style="line-height: 1.8;">
                <li><?php esc_html_e("Token version number", "zero-ad-network"); ?></li>
                <li><?php esc_html_e("Subscription expiration date", "zero-ad-network"); ?></li>
                <li><?php esc_html_e("Enabled features (Clean Web, One Pass, or both)", "zero-ad-network"); ?></li>
                <li><?php esc_html_e("Random seed value (for token uniqueness)", "zero-ad-network"); ?></li>
            </ul>
            <p style="line-height: 1.8; color: #666;">
                <strong><?php esc_html_e("Important:", "zero-ad-network"); ?></strong>
                <?php esc_html_e(
                  "No email addresses, names, IP addresses, or any personally identifiable information is included. Subscribers remain anonymous to your site.",
                  "zero-ad-network"
                ); ?>
            </p>
        </div>
    </div>

    <!-- Getting Started -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("🚀 Getting Started", "zero-ad-network"); ?></h2>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
            <div style="padding: 20px; border: 2px solid #0073aa; border-radius: 8px; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 10px;">1️⃣</div>
                <h3><?php esc_html_e("Register", "zero-ad-network"); ?></h3>
                <p><?php esc_html_e(
                  "Create an account at zeroad.network and register your WordPress site",
                  "zero-ad-network"
                ); ?></p>
                <a href="https://zeroad.network/login" target="_blank" class="button button-primary">
                    <?php esc_html_e("Register Now", "zero-ad-network"); ?>
                </a>
            </div>

            <div style="padding: 20px; border: 2px solid #0073aa; border-radius: 8px; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 10px;">2️⃣</div>
                <h3><?php esc_html_e("Configure", "zero-ad-network"); ?></h3>
                <p><?php esc_html_e(
                  "Enter your Client ID and select which features to enable",
                  "zero-ad-network"
                ); ?></p>
                <a href="<?php echo esc_url(
                  admin_url("admin.php?page=zeroad-token")
                ); ?>" class="button button-primary">
                    <?php esc_html_e("Go to Settings", "zero-ad-network"); ?>
                </a>
            </div>

            <div style="padding: 20px; border: 2px solid #0073aa; border-radius: 8px; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 10px;">3️⃣</div>
                <h3><?php esc_html_e("Earn", "zero-ad-network"); ?></h3>
                <p><?php esc_html_e(
                  "Start earning revenue as subscribers enjoy your content!",
                  "zero-ad-network"
                ); ?></p>
                <a href="https://zeroad.network/dashboard" target="_blank" class="button button-secondary">
                    <?php esc_html_e("View Dashboard", "zero-ad-network"); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Resources -->
    <div style="max-width: 900px; margin: 40px 0;">
        <h2><?php esc_html_e("📚 Resources & Support", "zero-ad-network"); ?></h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h3><?php esc_html_e("Documentation", "zero-ad-network"); ?></h3>
                <ul style="line-height: 2;">
                    <li><a href="https://docs.zeroad.network" target="_blank"><?php esc_html_e(
                      "Developer Portal",
                      "zero-ad-network"
                    ); ?></a></li>
                    <li><a href="https://docs.zeroad.network/site-integration" target="_blank"><?php esc_html_e(
                      "Integration Guide",
                      "zero-ad-network"
                    ); ?></a></li>
                    <li><a href="https://docs.zeroad.network/blog" target="_blank"><?php esc_html_e(
                      "Blog & Updates",
                      "zero-ad-network"
                    ); ?></a></li>
                </ul>
            </div>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <h3><?php esc_html_e("Get Help", "zero-ad-network"); ?></h3>
                <ul style="line-height: 2;">
                    <li><a href="https://zeroad.network/#faq" target="_blank"><?php esc_html_e(
                      "FAQs",
                      "zero-ad-network"
                    ); ?></a></li>
                    <li><a href="mailto:support@zeroad.network"><?php esc_html_e(
                      "Email Support",
                      "zero-ad-network"
                    ); ?></a></li>
                    <li><a href="https://zeroad.network/terms" target="_blank"><?php esc_html_e(
                      "Contact Us",
                      "zero-ad-network"
                    ); ?></a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="max-width: 900px; margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 8px; text-align: center;">
        <h3><?php esc_html_e("Join the Movement Toward a Better Web", "zero-ad-network"); ?></h3>
        <p style="font-size: 16px; line-height: 1.8; color: #666;">
            <?php esc_html_e(
              "By partnering with Zero Ad Network, you're not just creating a new revenue stream—you're participating in a movement to make the internet better for everyone. Together, we can build a web where quality content is rewarded, users are respected, and everyone wins.",
              "zero-ad-network"
            ); ?>
        </p>
        <p style="margin-top: 20px;">
            <a href="https://zeroad.network" target="_blank" class="button button-primary button-hero">
                <?php esc_html_e("Learn More at zeroad.network", "zero-ad-network"); ?>
            </a>
        </p>
    </div>
</div>