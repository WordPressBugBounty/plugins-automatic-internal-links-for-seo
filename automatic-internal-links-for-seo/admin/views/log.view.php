<div id="app_autolink_links" class="container-fluid ails-container">

    <?php include "inc/nav.view.php"; ?>

    <div class="row">

        <div class="col-xs-12">

            <div class="ails-card">

            <div v-if="total_items" v-cloak>
                <div class="ails-notice ails-notice-info">
                    <p>{{ total_items }} <?php echo __('items found with focus keyword. You can add them from Sync & Settings page.', "automatic-internal-links-for-seo"); ?>
                    <a href="admin.php?page=automatic-internal-links-for-seo" class="ails-btn" style="display: inline-block; margin-left: 10px; width: auto; padding: 3px 10px;"><?php echo __('GO TO SYNC & SETTINGS', "automatic-internal-links-for-seo"); ?></a>
                    </p>

                </div>
            </div>

                <div v-if="items.length" v-cloak>

                <div class="row middle-xs between-md">
                    <div class="col-xs-12 col-md-6">
                        <h2 class="ails-title"><?php echo __('Auto Activity Logs', 'automatic-internal-links-for-seo'); ?></h2>
                    </div>
                    <div class="col-xs-12 col-md-4">
                        <div class="ails-stats">
                        <?php echo __('Total number of keywords:', 'automatic-internal-links-for-seo'); ?> <strong>{{ items.length }}</strong>
                        </div>
                    </div>
                </div>

                    <div class="row middle-xs ails-log-header" style="margin: 0">
                        <div class="col-xs-12 col-md-4 col-lg-3">
                            <div class="row middle-xs" style="margin: 0">
                                <div class="col-xs-1" style="padding-left: 0.3rem">
                                    <input type="checkbox" v-model="selectAllCheckbox" @change="selectAll($event)" />
                                </div>
                                <div class="col-xs">
                                    <button v-if="ids.length" @click.prevent="bulkDelete(ids, 'log')" class="ails-btn ails-red" style="display: inline-block; width: auto; padding: 8px 14px; margin-left: 5px;" v-cloak><?php echo __('Delete Selected Items', 'automatic-internal-links-for-seo'); ?></button>
                                </div> 
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-2 col-lg-3 col-lg-offset-3">
                            <div class="row middle-xs" style="margin-bottom: 0">
                                <div class="col-xs-4 col-md-5">
                                    <label><?php echo __('Logs Per Page', 'automatic-internal-links-for-seo'); ?></label>
                                </div>
                                <div class="col-xs-8 col-md-7">
                                    <select class="ails-input" v-model="perPage">
                                        <option value="20">20</option>
                                        <option value="30">30</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="150">150</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-4 col-lg-3">
                            <input class="ails-input" type="text" v-model="search" placeholder="<?php echo __('Search', 'automatic-internal-links-for-seo'); ?>">
                        </div>
                    </div>

                    <paginate ref="paginator" name="items" :list="filteredItems" :per="Number(perPage)" :key="search" class="paginate-list">

                        <div v-for="item in paginated('items')" :key="item.id" class="ails-log-item">

                            <div class="row">
                                <div class="col-xs-2 col-md-1 ails-selected">
                                    <input type="checkbox" :value="item.id" v-model="ids"  style="margin-left: 10px" />
                                </div>
                                <div class="col-xs-10 col-md-1">
                                    <span class="ails-pt"
                                        :title="item.post_type">{{ truncate(item.post_type, 7) }}</span>
                                    <span v-else class="ails-pt"><?php echo __('Custom', 'automatic-internal-links-for-seo'); ?></span>
                                </div>
                                <div class="col-xs">
                                    "<a :href="'post.php?post='+item.post_id+'&action=edit'" target="_blank">{{ item.title }}</a>" <?php echo __('with keyword', 'automatic-internal-links-for-seo'); ?> <span class="fkw">{{ item.keyword }}</span>
                                    <span v-if="item.created_at == item.updated_at"><?php echo __('created at', 'automatic-internal-links-for-seo'); ?></span>
                                    <span v-else><?php echo __('updated at', 'automatic-internal-links-for-seo'); ?></span>
                                    <span v-html="moment(item.updated_at).format('MMMM Do, YYYY, h:mm:ss')"></span>.
                                    <a href="#" class="del" @click.prevent="deleteItem(item.id, 'log')"><?php echo __('Delete', 'automatic-internal-links-for-seo'); ?></a>
                                </div>

                            </div>

                        </div>

                    </paginate>

                    <div class="row bewteen-xs" style="margin-top: 15px">
                        <div class="col-xs-12 col-md-9">
                            <paginate-links @change="onPageChange" for="items" :limit="9" :show-step-links="true"></paginate-links>
                        </div>
                        <div class="col-xs-12 col-md-3" style="margin-top: 10px; text-align:right; padding-right: 15px">
                            <span
                                v-if="$refs.paginator"><?php echo __('Viewing', 'automatic-internal-links-for-seo'); ?>
                                {{ $refs.paginator.pageItemsCount }}
                                <?php echo __('logs', 'automatic-internal-links-for-seo'); ?></span>
                        </div>
                    </div>

                </div>

                <div class="ails-alert ails-note" style="font-size: 16px">
                    <?php echo sprintf( wp_kses( __( 'Note: Deleting items from logs will be added back if you use "Bulk Add" option again to syncronize all posts. Also the item will be added back if you update the post (as long as focus keyword exists). If you want to skip some pages/posts/products, make sure to disable them via <a href="%s" target="_blank">Meta Box</a> on post edit page and then delete it from logs (if its already added).', 'automatic-internal-links-for-seo' ), array( 
                        'a' => array( 
                            'href' => array(), 
                            'target' => array(), 
                        ),
                    ) ), esc_url( plugin_dir_url( __FILE__ ) . '../assets/metabox.png' ) );?>
                </div>

            </div>

        </div>

    </div>

</div>