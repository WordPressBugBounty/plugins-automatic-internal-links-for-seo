<div id="app_autolink_links" class="container-fluid ails-container ails-faq">

    <?php include "inc/nav.view.php"; ?>

    <div class="row">

        <div class="col-xs-12">

            <div class="ails-segment">

                <h2><?php echo __('Is it possible to use this plugin with another SEO plugin?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __('At the moment, the Auto internal links for SEO plugin is only compatible with Yoast SEO and Rank Math plugins. Upcoming updates will allow greater compatibility.', "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Is it possible to exclude a particular area from the text?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes. it's possible. You’ll need to “inspect” the element using DevTools. Get the CSS id and add it in the Exclude HTML Tags / ID / Class section like this: #container (if it's a class then like this: .skip-autolinks). All links inside that container will be skipped.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Is it possible to “blacklist” keyword from being used as anchors?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes. There is a section where you can enter all your keywords, one by line, they will be skipped during the process. Please note that excluded keywords are case sensitive by default. If your keyword is adding links to both cases, then make sure to include both (case sensitive & insensitive) on each line. e.g. “wordpress” in excluded keywords list will not skip “WordPress”. You can just add “WordPress” on new line to skip both.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div id="memory" class="ails-segment">

                <h2><?php echo __('What is WordPress memory limit and how can I increase it?', "automatic-internal-links-for-seo"); ?></h2>

                <p><?php echo __('WordPress memory limit is the maximum amount of memory that WordPress can use to run operations like generating pages, executing plugins or themes, and processing user requests. By default, WordPress memory limit is set to 40MB, but you can increase it to accommodate resource-intensive tasks or to avoid errors that occur when WordPress runs out of memory.', "automatic-internal-links-for-seo"); ?></p>

                <p><?php echo __('To increase WordPress memory limit, there are a few different methods you can try:', "automatic-internal-links-for-seo"); ?></p>

                <p><?php echo __('Edit wp-config.php file: You can increase the memory limit by adding the following line of code to your <strong>wp-config.php</strong> file:', "automatic-internal-links-for-seo"); ?></p>

                <p style="font-weight: 700; color: red;">define('WP_MEMORY_LIMIT', '64M');</p>
                
                <p style="margin-bottom: 0;"><?php echo __("This code sets the memory limit to 64MB. Place this code in the wp-config.php file, just before the line that says “That’s all, stop editing! Happy publishing.”", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div id="memory" class="ails-segment">

                <h2><?php echo __("Why there is a notification asking me to increase WordPress Memory Limit?", "automatic-internal-links-for-seo"); ?></h2>

                <p><?php echo __("If you have more than 1000 items (posts/pages/products) with focus keywords ready to fetch by Auto Internal Links Plugin OR more than 1000 items that are already synced to the Auto Internal Links Logs database table then the plugin will display a notification to increase the memory limit to 64M. Note that, in most cases, WordPress default memory limit of 40M is enough for more than 1000 items but it's for future proof, in case your posts/pages will keep increasing.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Is this plugin compatible with Advanced Custom Fields (ACF)?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("For the moment, this plugin is not compatible with ACF (as ACF use postmeta for custom field while this plugin is using the_content filter which doesn't support postmeta). ", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <h2><?php echo __('Does it work with scheduled publications?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes. As soon as your content is published, this plugin will detect your URL and focus keywords and will create links everywhere needed.", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <h2><?php echo __('Does the plugin consume a lot of server resources during the "auto-links" process?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("No! The SYNC & AUTO SYNC features take very little resources. Meaning that even a shared host should handle this continuous process nicely.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('About the PRO version, does it add links to product categories as well (tags, attributes, ...) - Category or Taxonomy pages?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("No. The SYNC process is only taking posts data. But it’s on our TODO list for future updates.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Is this plugin compatible with WPML or Polylang plugins?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Partially. Meaning that this plugin will detect all your pages, even those (duplicated) created with WPML, and will created links as expected. However, the Auto Internal Links plugin is not yet able to differentiate between identical words, used in different languages. For example, the word: “stress”, is spelled the same way in English and in French. This means that when it is detected as a “Focus Keyword”, it may happen that links to English pages are created in the French version of the site, and vice versa (the plugin is not capable, for the moment, to distinguish the languages used between the pages). This is a problem that we are trying to find a solution to.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Does this plugin exclude breadcrumb paths when creating links?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes. No relation to breadcrumbs.", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <h2><?php echo __('Is it possible to avoid a page/post/product from being used by the plugin?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes. You can use the META Box available on sidebar of your post to “unSync” a specific page.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('What type of external links should I create?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Regarding external links, we recommend applying 3 rules. The first is to always make an external link to an authority page for which search engines have no doubts as to its meaning (eg: Wikipedia). Then make sure you only make one such external link per page. Finally, be sure to include a NoFollow attribute in your link to maximize the performance of crawlers on YOUR site", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <h2><?php echo __('Should I use a Nofollow attribute for internal links?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("NO. It is not recommended to use the NoFollow attribute for internal links.", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <h2><?php echo __('Does this plugin support a wide range of character encodings?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("Yes, so far so good. Let us know if you are having an issue with this.", "automatic-internal-links-for-seo"); ?></p>

            </div>

            <div class="ails-segment">

                <h2><?php echo __('Is it possible to prevent that links created by the plugin are not redirecting to themselves?', "automatic-internal-links-for-seo"); ?></h2>

                <p style="margin-bottom: 0"><?php echo __("YES. This plugin was made to avoid this type of issue.", "automatic-internal-links-for-seo"); ?></p>

            </div>
            
            <div class="ails-segment">

                <p style="margin-bottom: 0"><?php echo __("Let us know if you have other questions. Contact us at", "automatic-internal-links-for-seo"); ?> <a href="mailto:support@better-robots.com">support@better-robots.com</a></p>

            </div>

        </div>

    </div>

</div>