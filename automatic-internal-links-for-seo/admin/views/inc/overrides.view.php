<h3 style="margin-top: 30px"><?php echo __('Override the Default Link Settings', 'automatic-internal-links-for-seo'); ?>
</h3>
<div class="ails-notice ails-note"><p style="font-size: 12px;"><?php echo __("Note: If you enable the override setting then it will be used for all links. Individual link settings will be ignored.", 'automatic-internal-links-for-seo'); ?></p></div>
<div class="row">
    <div class="col-xs-6 col-md-4 col-lg-4">
        <label class="ails-label"><?php echo __('Enable Override', 'automatic-internal-links-for-seo'); ?>
            <span
                tooltip="<?php echo __("Enable this option if you want to override the settings which you set while syncing items. It's a global setting, which will affect all the links.", 'automatic-internal-links-for-seo'); ?>"
                flow="right">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </label>
    </div>
    <div class="col-xs-6 col-md-8 col-lg-8">
        <label class="ails-toggle">
            <input id="enable_override" type="checkbox" name="enable_override" value="enable_override"
                <?php if ($options->check('enable_override')) echo "checked" ?> />
            <span class='ails-toggle-slider ails-toggle-round'></span>
        </label>
    </div>
</div>

<div id="override_options" <?php if ( $options::check('enable_override') ) { echo 'style="display: inline;"'; } else { echo 'style="display: none;"';} ?>>
    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('Max Links', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip="<?php echo __('Maximum number of links on a single page. Set -1 for unlimited links. 0 is not allowed. Disable it via meta box on post edit page', "automatic-internal-links-for-seo"); ?>"
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <input type="number" id="max_links" name="max_links" class="ails-input"
                placeholder="<?php echo __('0', 'automatic-internal-links-for-seo'); ?>"
                value="<?php if ($options->check('max_links'))  echo esc_html($options->get('max_links')); ?>" />
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('New Tab', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip="<?php echo __('Turn it on if you want link to open in a new tab', "automatic-internal-links-for-seo"); ?>"
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <label class="ails-toggle">
                <input type="checkbox" name="new_tab" value="new_tab"
                    <?php if ($options->check('new_tab')) echo "checked" ?> />
                <span class='ails-toggle-slider ails-toggle-round'></span>
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('No Follow', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip="<?php echo __('Turn it on if you want Search engines to NOT follow this link', "automatic-internal-links-for-seo"); ?>"
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <label class="ails-toggle">
                <input type="checkbox" name="nofollow" value="nofollow"
                    <?php if ($options->check('nofollow')) echo "checked" ?> />
                <span class='ails-toggle-slider ails-toggle-round'></span>
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('Partial Match', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip='<?php echo __('Allow pertial matching. e.g. "Word" keyword will link "Word" in WordPress.', "automatic-internal-links-for-seo"); ?>'
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <label class="ails-toggle">
                <input type="checkbox" name="partial_match" value="partial_match"
                    <?php if ($options->check('partial_match')) echo "checked" ?> />
                <span class='ails-toggle-slider ails-toggle-round'></span>
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('Bold Anchor', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip='<?php echo __('This option will make anchor text (keyword) as bold', "automatic-internal-links-for-seo"); ?>'
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <label class="ails-toggle">
                <input type="checkbox" name="bold" value="bold" <?php if ($options->check('bold')) echo "checked" ?> />
                <span class='ails-toggle-slider ails-toggle-round'></span>
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6 col-md-4 col-lg-4">
            <label class="ails-label"><?php echo __('Case Sensitive', 'automatic-internal-links-for-seo'); ?>
                <span
                    tooltip='<?php echo __("By default, case is insensitive. Turn it on if you want it Sensitive.", "automatic-internal-links-for-seo"); ?>'
                    flow="right">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
        </div>
        <div class="col-xs-6 col-md-8 col-lg-8">
            <label class="ails-toggle">
                <input type="checkbox" name="case_sensitive" value="case_sensitive"
                    <?php if ($options->check('case_sensitive')) echo "checked" ?> />
                <span class='ails-toggle-slider ails-toggle-round'></span>
            </label>
        </div>
    </div>
</div>