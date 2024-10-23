<div class="container-fluid ails-container">

    <?php 
if ( $total_items_require_sync && $total_items_require_sync['items'] > 1000 || $total_items_in_logs > 1000 ) {
    echo $memory_notification;
}
?>

    <?php 
include "inc/nav.view.php";
?>

    <?php 
if ( !empty( $updated ) ) {
    ?>
    <div class="ails-notice ails-notice-success">
        <p>
            <strong><?php 
    echo esc_html( $updated );
    ?></strong>
        </p>
    </div>
    <?php 
}
?>

    <?php 
if ( !empty( $deleting ) ) {
    ?>
    <div class="ails-notice ails-notice-success">
        <p>
            <strong><?php 
    echo esc_html( $deleting );
    ?></strong>
        </p>
    </div>
    <?php 
}
?>

    <div class="row">

        <div class="col-xs-12 col-md-8 col-lg-9">

            <div class="ails-segment" style="margin: 0 0 15px">

                <p><strong><?php 
echo __( 'ABOUT:', 'automatic-internal-links-for-seo' );
?></strong>
                    <?php 
echo __( '"Automatic links for SEO" is a Wordpress plugin that will make your life easier. The internal linking process is a very important SEO factor for several reasons (avoid deep pages, orphan pages, ...). But this is often a tedious process and most of the existing solutions are manual. This is where "Automatic link for SEO" comes in. A priori, if you are interested in internal linking, it is because you already have good SEO practices and this includes the use of META-data. Since Yoast SEO and Rank Math are the most popular plugins for optimizing META datas, we have chosen to base ourselves on them to allow the rapid deployment of consistent and efficient links.', 'automatic-internal-links-for-seo' );
?>
                </p>

            </div>

            <div class="ails-card ails-form">

                <form method="post">

                    <?php 
wp_nonce_field( 'automatic-internal-links-settings', 'ails__nonce' );
?>

                    <h2 class="ails-title"><?php 
echo __( 'STEP 1: Settings', 'automatic-internal-links-for-seo' );
?>
                    </h2>

                    <h3><?php 
echo __( 'Where to Apply', 'automatic-internal-links-for-seo' );
?></h3>

                    <div class="row">
                        <div class="col-xs-6 col-md-4 col-lg-4">
                            <label for="apply_pages"
                                class="ails-label"><?php 
echo __( 'Select Post Types', 'automatic-internal-links-for-seo' );
?>
                                <span
                                    tooltip="<?php 
echo __( 'Select post types where you want to apply internal links. This setting will also reflect on other parts of this Plugin. Check Help & FAQ for more details.', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-6 col-md-8 col-lg-8">
                            <?php 
foreach ( $post_types as $label => $post_type ) {
    ?>

                            <div class="ails-checkbox">
                                <input id="<?php 
    echo esc_html( "ails-" . $post_type );
    ?>" type="checkbox"
                                    
                                    <?php 
    if ( !ails__fs()->can_use_premium_code__premium_only() && $post_type == 'product' ) {
        echo 'disabled';
    } else {
        echo "name='post_types[]' value=" . esc_html( $post_type );
    }
    ?>
                                    <?php 
    if ( $options->check( 'post_types' ) && in_array( $post_type, maybe_unserialize( $options->get( 'post_types' ) ) ) ) {
        echo "checked";
    }
    ?> />

                                <label
                                    for="<?php 
    echo esc_html( "ails-" . $post_type );
    ?>"><?php 
    echo esc_html( $label ) . (( !ails__fs()->can_use_premium_code__premium_only() && $post_type == 'product' ? " (PRO only)" : '' ));
    ?></label>
                            </div>

                            <?php 
}
?>

                            <?php 
?>
                            <div class="ails-alert ails-info" style="margin-bottom: 0">
                                <?php 
echo sprintf( wp_kses( __( '<a href="%s">Get Pro version</a> to enable Woocommerce Products', "automatic-internal-links-for-seo" ), array(
    'a' => array(
        'href'   => array(),
        'target' => array(),
    ),
) ), esc_url( "admin.php?page=automatic-internal-links-for-seo-pricing" ) );
?>
                            </div>
                            <?php 
?>
                        </div>
                    </div>
                    
                    <div class="row ails-pro">
                        <div class="col-xs-12">
                            <h3><?php 
echo __( 'Auto-Links Continuously', 'automatic-internal-links-for-seo' );
echo __( ' (Pro Only)', 'automatic-internal-links-for-seo' );
?></h3>
                            <h4>100% Automatic - No more manual action required.</h4>
                        </div>
                        <div class="col-xs-6 col-md-4 col-lg-4">
                            <?php 
?>
                            <label
                                class="ails-label"><?php 
echo __( 'Auto-Links on Post Create/Update', 'automatic-internal-links-for-seo' );
?>
                                <span
                                    tooltip="<?php 
echo __( 'This option will sync pages with logs in real-time while you create or update a post (or any other post type selected above).', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                            <?php 
?>
                        </div>
                        <div class="col-xs-6 col-md-8 col-lg-8">
                            <?php 
?>
                            <label class="ails-toggle"><input type="checkbox" disabled ?> /><span class='ails-toggle-slider ails-toggle-round'></span></label>

                            <span class="ails-line"><?php 
echo sprintf( wp_kses( __( '<a href="%s">Get Pro version</a> to enable Sync/Auto Links Continuously feature', "automatic-internal-links-for-seo" ), array(
    'a' => array(
        'href'   => array(),
        'target' => array(),
    ),
) ), esc_url( "admin.php?page=automatic-internal-links-for-seo-pricing" ) );
?></span>
                            <?php 
?>
                        </div>
                    </div>

                    <h3 style="margin-top: 30px"><?php 
echo __( 'Where to Not Apply', 'automatic-internal-links-for-seo' );
?></h3>

                    <div class="row">
                        <div class="col-xs-6 col-md-4 col-lg-4">
                            <label for="exclude_tags"
                                class="ails-label"><?php 
echo __( "Black List URL's", 'automatic-internal-links-for-seo' );
?>
                                <span
                                    tooltip="<?php 
echo __( 'Enter URL on each line to skip incoming internal links. For skipping outgoing links, go & disable the page via metabox (Pro feature) or remove it from logs', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-6 col-md-8 col-lg-8">
                            <textarea id="blacklist" name="blacklist" class="ails-textarea"
                            placeholder="Enter URL on each line"><?php 
echo esc_html( $blacklist );
?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6 col-md-4 col-lg-4">
                            <label for="exclude_tags"
                                class="ails-label"><?php 
echo __( 'Exclude HTML Tags / ID / Class', 'automatic-internal-links-for-seo' );
?>
                                <span
                                    tooltip="<?php 
echo __( 'You can add HTML Tags / ID / Class on each line and the text inside those tags will be automatically skipped', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-6 col-md-8 col-lg-8">
                            <textarea id="exclude_tags" name="exclude_tags" class="ails-textarea" placeholder="<?php 
echo __( 'Enter HTML Tag/ID/Classname on each line. e.g. h2 or #id or .ad', 'automatic-internal-links-for-seo' );
?>"><?php 
if ( $options->check( 'exclude_tags' ) ) {
    echo esc_html( $options->get( 'exclude_tags' ) );
}
?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-6 col-md-4 col-lg-4">
                            <label for="exclude_tags"
                                class="ails-label"><?php 
echo __( 'Exclude Keywords', 'automatic-internal-links-for-seo' );
?>
                                <span
                                    tooltip="<?php 
echo __( 'Excluded keyword should be exactly same as keyword added in Activity log or Manual Links.', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-6 col-md-8 col-lg-8">
                            <textarea id="exclude_keywords" name="exclude_keywords" class="ails-textarea" placeholder="<?php 
echo __( 'Enter Keyword on each line.', 'automatic-internal-links-for-seo' );
?>"><?php 
if ( $options->check( 'exclude_keywords' ) ) {
    echo esc_html( $options->get( 'exclude_keywords' ) );
}
?></textarea>

                            <p class="ails-notice ails-note" style="padding: 10px; margin-bottom: 0"><?php 
echo __( 'Note: Excluded keyword should be exactly same as keyword added in Activity log or Manual Links.', 'automatic-internal-links-for-seo' );
?></p>
                        </div>
                    </div>

                    <?php 
include "inc/overrides.view.php";
?>

                    <div class="row middle-xs" style="margin-top: 30px">
                        <div class="col-xs-6 col-md-4">
                            <label for="remove_settings"
                                class="ails-label"><?php 
echo __( 'Remove Data on Plugin Deactivation', 'automatic-internal-links-for-seo' );
?>
                                <span tooltip="<?php 
echo __( 'This option will remove all settings and data (Activity logs & Manual internal links) on plugin deactivation. Use it with caution. This action is irreversible.', 'automatic-internal-links-for-seo' );
?>"
                                    flow="right">
                                    <i class="dashicons dashicons-editor-help"></i>
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-6 col-md-2">
                            <label class="ails-toggle"><input id="remove_settings" type="checkbox"
                                    name="remove_settings" value="remove_settings"
                                    <?php 
if ( $options->check( 'remove_settings' ) ) {
    echo "checked";
}
?> /><span
                                    class='ails-toggle-slider ails-toggle-round'></span></label>
                        </div>

                        <div class="col-xs-12 col-md-6">
                            <input type="submit" name="update"
                                value="<?php 
echo __( 'Update Settings', 'automatic-internal-links-for-seo' );
?>"
                                class="ails-btn" style="font-size: 24px;">
                        </div>
                    </div>

                </form>

            </div>

            <div id="app_autolink_links" class="ails-card ails-form" style="margin-top: 15px">
                
                <?php 
?>
                    <div class="ails-alert ails-info" style="margin-bottom: 0">
                        <?php 
echo sprintf( wp_kses( __( '<a href="%s">Get Pro version</a> to SYNC Woocommerce Products', "automatic-internal-links-for-seo" ), array(
    'a' => array(
        'href'   => array(),
        'target' => array(),
    ),
) ), esc_url( "admin.php?page=automatic-internal-links-for-seo-pricing" ) );
?>
                    </div>
                <?php 
?>

                <?php 
include "inc/sync.view.php";
?>
                
                <h3><?php 
echo __( 'Acivity Log and Cache', 'automatic-internal-links-for-seo' );
?></h3>
                <p style="margin: 15px 0;">
                    <?php 
echo __( 'You can check the activity log (the posts/pages you synced) by clicking the green "Check Activity Log" button. There is a transient cache on front-end (home page and other pages). If you synced more items or deleted items from the activity log page, and you still don\'t see changes on your website (either links are not being added, after changing content or removed, in case you deleted keywords). Click on the red "Clear Transient Cache" button and that should fix the cache issue. Please note, that this cache button doesn\'t affect any other cache plugin you have installed, neither it will clear that cache. This button can fix cache issues only related to Automatic Internal Links plugin. It is not intended to use if Fetch or Sync process is stuck. In that case, you can Stop the process and try again.', 'automatic-internal-links-for-seo' );
?>
                </p>

                <div class="ails-flex">
                    <a href="admin.php?page=automatic-internal-links-logs" class="ails-btn" style="margin-top: 15px; display: inline-block; width: auto;"><?php 
echo __( 'Check Activity log', 'automatic-internal-links-for-seo' );
?></a>

                    <form method="post">

                        <?php 
wp_nonce_field( 'automatic-internal-links-settings', 'ails__nonce' );
?>

                        <input type="submit" name="clear_transients" class="ails-btn red" value="<?php 
echo __( 'Clear Transients Cache', 'automatic-internal-links-for-seo' );
?>" style="margin-top: 15px; display: inline-block; width: auto;" />

                    </form>
                </div>

                <?php 
?>
                <div class="ails-notice ails-note" style="margin-top: 20px;">
                    <h2><?php 
echo __( 'PRO Features:', 'automatic-internal-links-for-seo' );
?></h2>
                    <ol>
                        <li><?php 
echo __( 'AUTO LINKS CONTINUOUSLY (No more manual action required)', 'automatic-internal-links-for-seo' );
?></li>
                        <li><?php 
echo __( 'Enable PRODUCT PAGES to sync', 'automatic-internal-links-for-seo' );
?></li>
                        <li><?php 
echo __( 'Enable PRODUCT PAGES with INTERNAL/EXTERNAL LINKS', 'automatic-internal-links-for-seo' );
?></li>
                        <li><?php 
echo sprintf( wp_kses( __( 'Add <a href="%s" target="_blank" style="border-bottom: 1px dotted">META BOX</a> everywhere for manual exclusions.', "better-robots-txt" ), array(
    'a' => array(
        'href'   => array(),
        'target' => array(),
        'style'  => array(),
    ),
) ), esc_url( AILS_PLUGIN_DIR . '/admin/assets/metabox.png' ) );
__( '', 'automatic-internal-links-for-seo' );
?></li>
                    </ol>
                    <a href="admin.php?page=automatic-internal-links-for-seo-pricing" class="ails-btn" style="display: inline-block; width: auto; margin-left: 15px; margin-bottom: 15px;"><?php 
echo __( 'Upgrade to PRO', 'automatic-internal-links-for-seo' );
?></a>
                </div>
                <?php 
?>
                
            </div>

            <div class="ails-card" style="margin-top: 15px">
                <h3><?php 
echo __( 'Auto Focus Keyword for SEO', 'automatic-internal-links-for-seo' );
?></h3>
                <p style="margin: 15px 0;">
                <?php 
echo sprintf( __( 'You don\'t have "Focus Keywords" on all of your pages and would like to create more internal links? We have a solution! Check <a href="%s" target="_blank"><strong>this plugin</strong></a>. It will allow you to add "Focus Keywords" in Yoast or Rank Math (on the backend), from your page titles, thus enabling you to use our plugin "Auto-links for SEO" more extensively on your site and create more internal linking', 'automatic-internal-links-for-seo' ), 'https://wordpress.org/plugins/auto-focus-keyword-for-seo/' );
?>

                
                </p>
            </div>

        </div>

        <div class="col-xs-12 col-md-4 col-lg-3">

            <?php 
include "inc/side.view.php";
?>

        </div>


    </div>

</div>