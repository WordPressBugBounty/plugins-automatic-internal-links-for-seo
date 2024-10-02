<div class="ails-card ails-form" v-cloak>

    <h2><?php echo __('Manual Custom Links', 'automatic-internal-links-for-seo'); ?></h2>

    <div v-if="errors.length" class="ails-errors">
        <b><?php echo __('Please correct the following error(s):', 'automatic-internal-links-for-seo'); ?></b>
        <ul>
        <li v-for="error in errors">{{ error }}</li>
        </ul>
    </div>

    <form @submit="addItem" method="post">
        <div class="ails-switch-radio">
        
            <input type="radio" id="use_custom_false" name="use_custom" v-model="form.use_custom" value="0" @change="useCustom('false')" required  />
            <label for="use_custom_false" style="width: 37%"><?php echo esc_html__( 'Internal Link', 'automatic-internal-links-for-seo' ); ?></label>
        
            <input type="radio" id="use_custom_true" name="use_custom" v-model="form.use_custom" value="1" @change="useCustom('true')" />
            <label for="use_custom_true" style="width: 37%"><?php echo esc_html__( 'External Link', 'automatic-internal-links-for-seo' ); ?></label>  
        
        </div>
        
        <label class="ails-label"><?php echo __('Keyword', 'automatic-internal-links-for-seo'); ?>
        <span tooltip="<?php echo __('Other than Focus keyword', "automatic-internal-links-for-seo"); ?>" flow="right"><i class="dashicons dashicons-editor-help"></i></span>
        </label>
        <input class="ails-input" type="text" name="keyword" v-model="form.keyword">

        <label class="ails-label"><?php echo __('Title', 'automatic-internal-links-for-seo'); ?>
        <span tooltip="<?php echo __('This is an internal title to differentiate between links. It will be used as title attribute as well', "automatic-internal-links-for-seo"); ?>" flow="right"><i class="dashicons dashicons-editor-help"></i></span>
        </label>
        <input class="ails-input" type="text" name="title" v-model="form.title">
        
        <div v-if="form.use_custom == '1'">
            <label class="ails-label"><?php echo __('URL', 'automatic-internal-links-for-seo'); ?></label>
            <input class="ails-input" type="text" name="url" v-model="form.url">
        </div>
        
        <div v-else>
        
            <label class="ails-label"><?php echo __('Select a Page', 'automatic-internal-links-for-seo'); ?><span tooltip="<?php echo __('You can select a post, page or any other post type which is selected on Settings page', "automatic-internal-links-for-seo"); ?>" flow="right"><i class="dashicons dashicons-editor-help"></i></span></label>
            <v-select label="title" :options="all_pages" :reduce="title => title.id" v-model="selected"></v-select>
            
        </div>
        
        <div class="row between-xs" style="margin-bottom: 0">
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('Priority', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip="<?php echo __('The higher priority will take over. e.g. Priority 1 will take over priority 0. If both have same priority then most recently added link will take precedence', "automatic-internal-links-for-seo"); ?>" flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <input class="ails-input" type="number" name="priority" v-model="form.priority">
            </div>
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('Max Links', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip="<?php echo __('Maximum number of links on a single page. Set -1 for unlimited links. 0 is not allowed. You can set link Status to disable instead.', "automatic-internal-links-for-seo"); ?>" flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <input class="ails-input" type="number" name="max_links" v-model="form.max_links">
            </div>
        </div>
        
        <div class="row between-xs" style="margin-bottom: 0">
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('New Tab', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip="<?php echo __('Turn it on if you want link to open in a new tab', "automatic-internal-links-for-seo"); ?>" flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <label class="ails-toggle"><input type="checkbox" name="new_tab" v-model="form.new_tab" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
            </div>
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('No Follow', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip="<?php echo __('Turn it on if you want Search engines to NOT follow this link', "automatic-internal-links-for-seo"); ?>" flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <label class="ails-toggle"><input type="checkbox" name="nofollow" v-model="form.nofollow" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
            </div>
        </div>
        
        <div class="row between-xs" style="margin-bottom: 0">
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('Partial Match', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip='<?php echo __('Allow pertial matching. e.g. "Word" keyword will link "Word" in WordPress.', "automatic-internal-links-for-seo"); ?>' flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <label class="ails-toggle"><input type="checkbox" name="partial_match" v-model="form.partial_match" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
            </div>
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('Bold Anchor', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip='<?php echo __("This option will make anchor text (keyword) as bold", "automatic-internal-links-for-seo"); ?>' flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <label class="ails-toggle"><input type="checkbox" name="bold" v-model="form.bold" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
            </div>
        </div>

        <div class="row between-xs">
            <div class="col-xs-5">
                <label class="ails-label"><?php echo __('Case Sensitive', 'automatic-internal-links-for-seo'); ?>
                    <span tooltip='<?php echo __("By default, case is insensitive. Turn it on if you want it Sensitive.", "automatic-internal-links-for-seo"); ?>' flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
                </label>
                <label class="ails-toggle"><input type="checkbox" name="case_sensitive" v-model="form.case_sensitive" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
            </div>
        </div>

        <input type="submit" value="<?php echo __('Create', 'automatic-internal-links-for-seo'); ?>" :class="['ails-btn ails-transition', disabled ? 'ails-disabled' : '']" style="margin-top: 20px" :disabled="disabled">
        
    </form>

</div>