<h2 class="ails-title"><?php echo __('STEP 2: Sync', "automatic-internal-links-for-seo"); ?></h2>

<div v-if="total_items" v-cloak>

    <div v-if="errors && errors.length > 0" class="ails-notice ails-notice-error" v-cloak>
        <p><?php echo __('Please fix the following errors:', "automatic-internal-links-for-seo"); ?></p>
        <ul>
            <li v-for="(error, index) in errors" :key="index" style="list-style: disc; margin-left: 15px;">{{ error }}</li>
        </ul>
    </div>
    
    <div class="ails-notice ails-notice-info">
        <p>{{ total_items }} <?php echo __('published items found with focus keyword(s). Click "Fetch Items" button to get ready for Sync.', "automatic-internal-links-for-seo"); ?></p>
    </div>

    <div>
        <button type="submit" @click.prevent="stopFlag = false, bulkFetch(), disabled = true" :class="['ails-btn bulk ails-transition ', disabled ? 'ails-disabled' : '']" :disabled="disabled">{{ stopFetchBtn ? "<?php echo __("Fetching...", "automatic-internal-links-for-seo"); ?>" : "<?php echo __("Fetch Items", "automatic-internal-links-for-seo"); ?>"}}</button>

        <button v-if="stopFetchBtn" @click.prevent="stopFlag = true, disabled = false, stopFetchBtn = false" class="ails-btn danger bulk ails-transition" style="margin-left: 5px;">Stop</button>

        <span v-if="data.syncDate" style="display: inline-block; margin-left: 10px;"><?php echo __("Last Synced:", "automatic-internal-links-for-seo"); ?> {{ data.syncDate }}</span>
    </div>

    <p v-if="stopFetchBtn" class="ails-notice ails-note" style="padding: 10px; margin-bottom: 0">Do not close or move from this page. Progress will be cancelled.</p>

    <br />
    <div v-if="progress" class="progress-container">
        <div class="progress" :style="{width: `${progress}`+'%'}"></div>
        <div class="percentage" :style="{left: `${progress}`+'%'}">{{ progress }}%</div>
    </div>
    <br />
    
    <div v-if="sync && syncRequired">

        <div v-if="syncRequired.length > 0" class="ails-notice ails-notice-info">
            <p>Great. You've {{ syncRequired.length }} <?php echo __('items in the waiting list. Tweak settings (if you want) and hit that <strong>SYNC NOW</strong> button.', "automatic-internal-links-for-seo"); ?></p>
        </div>

        <div v-else v-cloak class="ails-notice ails-notice-success">
            <p><?php echo __("All Good. No new keyword to be synchronized.", "automatic-internal-links-for-seo"); ?></p>
        </div>

        <div v-if="syncRequired.length > 0" class="ails-sync">

            <div class="row between-xs">
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('Priority', "automatic-internal-links-for-seo"); ?>
                        <span
                            tooltip="<?php echo __('The higher priority will take over. e.g. Priority 1 will take over priority 0. If both have same priority then most recently added link will take precedence', "automatic-internal-links-for-seo"); ?>"
                            flow="right">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <input class="ails-input" type="number" name="priority" v-model="form.priority">
                </div>
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('Max Links', "automatic-internal-links-for-seo"); ?>
                        <span
                            tooltip="<?php echo __('Maximum number of links on a single page. Set -1 for unlimited links. 0 is not allowed. Disable it via meta box on post edit page', "automatic-internal-links-for-seo"); ?>"
                            flow="right">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <input class="ails-input" type="number" name="max_links" v-model="form.max_links">
                </div>
        
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('New Tab', "automatic-internal-links-for-seo"); ?>
                        <span tooltip="<?php echo __('Turn it on if you want link to open in a new tab', "automatic-internal-links-for-seo"); ?>" flow="right">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <label class="ails-toggle"><input type="checkbox" name="new_tab" v-model="form.new_tab" /><span
                            class='ails-toggle-slider ails-toggle-round'></span></label>
                </div>
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('No Follow', "automatic-internal-links-for-seo"); ?>
                        <span tooltip="<?php echo __('Turn it on if you want Search engines to NOT follow this link', "automatic-internal-links-for-seo"); ?>" flow="right">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <label class="ails-toggle">
                        <input type="checkbox" name="nofollow" v-model="form.nofollow" /><span
                            class='ails-toggle-slider ails-toggle-round'></span>
                    </label>
                </div>
        
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('Partial Match', "automatic-internal-links-for-seo"); ?>
                        <span tooltip='<?php echo __('Allow pertial matching. e.g. "Word" keyword will link "Word" in WordPress.', "automatic-internal-links-for-seo"); ?>'
                            flow="left">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <label class="ails-toggle"><input type="checkbox" name="partial_match"
                            v-model="form.partial_match" /><span
                            class='ails-toggle-slider ails-toggle-round'></span></label>
                </div>

                <div class="col-xs">
                    <label class="ails-label"><?php echo __('Bold Anchor', "automatic-internal-links-for-seo"); ?>
                        <span tooltip='<?php echo __('This option will make anchor text (keyword) as bold', "automatic-internal-links-for-seo"); ?>'
                            flow="left">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <label class="ails-toggle"><input type="checkbox" name="bold"
                            v-model="form.bold" /><span
                            class='ails-toggle-slider ails-toggle-round'></span></label>
                </div>
        
                <div class="col-xs">
                    <label class="ails-label"><?php echo __('Case Sensitive', "automatic-internal-links-for-seo"); ?>
                        <span tooltip='<?php echo __("By default, case is insensitive. Turn it on if you want it Sensitive.", "automatic-internal-links-for-seo"); ?>' flow="left">
                            <i class="dashicons dashicons-editor-help"></i>
                        </span>
                    </label>
                    <label class="ails-toggle"><input type="checkbox" name="case_sensitive"
                            v-model="form.case_sensitive" /><span
                            class='ails-toggle-slider ails-toggle-round'></span></label>
                </div>
                <div class="col-xs-12" style="margin-top: 15px; margin-bottom: 0;">
                    <button type="submit" @click.prevent="stopFlag = false, bulkAdd()" :class="['ails-btn bulk ails-transition ', disabled ? 'ails-disabled' : '']" :disabled="disabled">{{ stopStoreBtn ? "<?php echo __("Processing...", "automatic-internal-links-for-seo"); ?>" : "<?php echo __("Sync Now", "automatic-internal-links-for-seo"); ?>"}}</button>

                    <button v-if="stopStoreBtn" @click.prevent="stopFlag = true, bulkStop()" class="ails-btn danger bulk ails-transition" style="margin-left: 5px;">Stop</button>
                </div>
                
            </div>
        
        </div>

        <div v-if="storingProgress" class="progress-container" style="margin-top: 15px;">
            <div class="progress" :style="{width: `${storingProgress}`+'%'}"></div>
            <div class="percentage" :style="{left: `${storingProgress}`+'%'}">{{ storingProgress }}%</div>
        </div>
    
        <ul class="ails-log" v-if="logs.length" v-cloak>
            <li v-for="(log, i) in logs" :key="i" :class="[log.status == 400 ? 'error' : '']">
                <div v-if="log.status == 200">
                    [{{ log.created_at }}] "{{ log.title }}" <?php echo __("link has been created successfully.", "automatic-internal-links-for-seo"); ?>
                </div>
                <div v-if="log.status == 400">
                    [{{ log.post_type }} ID: {{ log.post_id }}] <?php echo __("Failed!!!", "automatic-internal-links-for-seo"); ?> <span v-for="(error, i) in log.errors" :key="i">•
                        {{ error }} </span>
                </div>
                <div v-if="log.status == 100" class="done">
                <?php echo __("All Done. Page should be refresh in {{ count }} seconds.", "automatic-internal-links-for-seo"); ?>
                </div>
            </li>
        </ul>
    </div>
</div>

<div v-else v-cloak>
    <div class="ails-notice ails-notice-success">
        <p><?php echo __("All Good. No new keyword to be synchronized.", "automatic-internal-links-for-seo"); ?></p>
    </div>
    <p v-if="data.syncDate"><?php echo __("Last Sync:", "automatic-internal-links-for-seo"); ?> {{ data.syncDate }}</p>
</div>