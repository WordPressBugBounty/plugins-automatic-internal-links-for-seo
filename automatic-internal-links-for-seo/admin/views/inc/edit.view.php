<div class="ails-card ails-form" v-cloak>

        <button class="ails-close" @click.prevent="showEdit = false, selected = '', errors = []">
            <svg viewBox="0 0 20 20">
                <path fill="#fff" d="M15.898,4.045c-0.271-0.272-0.713-0.272-0.986,0l-4.71,4.711L5.493,4.045c-0.272-0.272-0.714-0.272-0.986,0s-0.272,0.714,0,0.986l4.709,4.711l-4.71,4.711c-0.272,0.271-0.272,0.713,0,0.986c0.136,0.136,0.314,0.203,0.492,0.203c0.179,0,0.357-0.067,0.493-0.203l4.711-4.711l4.71,4.711c0.137,0.136,0.314,0.203,0.494,0.203c0.178,0,0.355-0.067,0.492-0.203c0.273-0.273,0.273-0.715,0-0.986l-4.711-4.711l4.711-4.711C16.172,4.759,16.172,4.317,15.898,4.045z"></path>
            </svg>
        </button>

    <h2><?php echo __('Edit the link', 'automatic-internal-links-for-seo'); ?></h2>

    <div v-if="errors.length" class="ails-errors">
        <b><?php echo __('Please correct the following error(s):', 'automatic-internal-links-for-seo'); ?></b>
        <ul>
        <li v-for="error in errors">{{ error }}</li>
        </ul>
    </div>
    
    <label class="ails-label"><?php echo __('Title', 'automatic-internal-links-for-seo'); ?></label>
    <input class="ails-input" type="text" name="title" v-model="item.title">

    <label class="ails-label"><?php echo __('Keyword', 'automatic-internal-links-for-seo'); ?></label>
    <input class="ails-input" type="text" name="keyword" v-model="item.keyword">

    <div v-if="item.use_custom == '1'">
        <label class="ails-label"><?php echo __('URL', 'automatic-internal-links-for-seo'); ?></label>
        <input class="ails-input" type="text" name="url" v-model="item.url">
    </div>

    <div v-else>

        <label class="ails-label"><?php echo __('Select a Page / Post / Product', 'automatic-internal-links-for-seo'); ?></label>
        <v-select label="title" :options="all_pages" :reduce="item => item.id" v-model="item.post_id"></v-select>
        
    </div>

    <div class="row between-xs" style="margin-bottom: 0">
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('Priority', 'automatic-internal-links-for-seo'); ?>
                <span tooltip="The higher priority will take over. e.g. Priority 1 will take over priority 0. If both have same priority then most recently added link will take precedence" flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <input class="ails-input" type="number" name="priority" v-model="item.priority">
        </div>
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('Max Links', 'automatic-internal-links-for-seo'); ?>
                <span tooltip="Maximum number of links on a single page. Set -1 for unlimited links. 0 is not allowed. You can set Status to disable instead." flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <input class="ails-input" type="number" name="max_links" v-model="item.max_links">
        </div>
    </div>

    <div class="row between-xs" style="margin-bottom: 0">
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('New Tab', 'automatic-internal-links-for-seo'); ?>
                <span tooltip="Turn it on if you want link to open in a new tab" flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <label class="ails-toggle"><input type="checkbox" name="new_tab" v-model="item.new_tab" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
        </div>
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('No Follow', 'automatic-internal-links-for-seo'); ?>
                <span tooltip="Turn it on if you want Search engines to NOT follow this link" flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <label class="ails-toggle"><input type="checkbox" name="nofollow" v-model="item.nofollow" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
        </div>
    </div>

    <div class="row between-xs" style="margin-bottom: 0">
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('Partial Match', 'automatic-internal-links-for-seo'); ?>
                <span tooltip='Allow pertial matching. e.g. "Word" keyword will link "Word" in WordPress' flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <label class="ails-toggle"><input type="checkbox" name="partial_match" v-model="item.partial_match" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
        </div>
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('Bold Anchor', 'automatic-internal-links-for-seo'); ?>
                <span tooltip='<?php echo __("This option will make anchor text (keyword) as bold", "automatic-internal-links-for-seo"); ?>' flow="right">
                        <i class="dashicons dashicons-editor-help"></i>
                    </span>
            </label>
            <label class="ails-toggle"><input type="checkbox" name="bold" v-model="item.bold" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
        </div>
    </div>

    <div class="row between-xs">
        <div class="col-xs-5">
            <label class="ails-label"><?php echo __('Case Sensitive', 'automatic-internal-links-for-seo'); ?>
                <span tooltip='By default, case is insensitive. Turn it on if you want it Sensitive.' flow="left">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </label>
            <label class="ails-toggle"><input type="checkbox" name="case" v-model="item.case_sensitive" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
        </div>
    </div>

    <button class="ails-btn" style="margin-top: 20px" @click.prevent="updateItem(item.id)"><?php echo __('Update', 'automatic-internal-links-for-seo'); ?></button>

</div>