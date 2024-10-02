<div id="app_autolink_links" class="container-fluid ails-container">

    <?php include "inc/nav.view.php"; ?>

    <div class="row">

        <transition name="slide-fade">

        <div v-if="!showEdit" class="col-xs-12 col-md-4 col-lg-3">
            
            <?php include "inc/add.view.php"; ?>

        </div>

        </transition>

        <div class="col-xs-12 col-md-8 col-lg-9">
            
            <div class="ails-card ails-form" v-cloak>

                <div v-if="items.length">

                    <div class="row" style="min-height: 37px">
                        <div class="col-xs-4 col-md-4 col-lg-3">
                            <button v-if="ids.length" @click.prevent="bulkDelete(ids)" class="ails-btn ails-red" v-cloak><?php echo __('Delete Selected Items', 'automatic-internal-links-for-seo'); ?></button>
                        </div>
                        <div class="col-xs-4 col-md-4 col-lg-3 col-lg-offset-3">
                            <div class="row middle-xs" style="margin-bottom: 0">
                                <div class="col-xs-6">
                                    <label><?php echo __('Links Per Page', 'automatic-internal-links-for-seo'); ?></label>
                                </div>
                                <div class="col-xs-6">
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
                        <div class="col-xs-4 col-md-4 col-lg-3">
                            <input class="ails-input" type="text" v-model="search" placeholder="<?php echo __('Search', 'automatic-internal-links-for-seo'); ?>">
                        </div>
                    </div>
                
                <paginate ref="paginator" name="items" :list="filteredItems" :per="Number(perPage)" :key="search" class="paginate-list">

                        <div class="ails-item-header">
                            <div class="row">
                                <div class="col-xs-1" style="max-width: 2.33333333%;">
                                    <input type="checkbox" v-model="selectAllCheckbox" @change="selectAll($event)" />
                                </div>
                                <div class="col-xs-1" style="text-align: center;">
                                <?php echo __('Type', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs">
                                <?php echo __('Title', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs-3">
                                <?php echo __('Keyword', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs-1">
                                <?php echo __('Priority', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs-1">
                                <?php echo __('Status', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs-1">
                                <?php echo __('Edit', 'automatic-internal-links-for-seo'); ?>
                                </div>
                                <div class="col-xs-1">
                                <?php echo __('Delete', 'automatic-internal-links-for-seo'); ?>
                                </div>
                            
                            </div>
                        </div>

                    <div v-for="item in paginated('items')" :key="item.id">

                        <div :class="['ails-item ails-transition', item.status == '0' ? 'disabled' : '']">

                            <div class="row">
                                <div class="col-xs-1" style="max-width: 2.33333333%;">
                                    <input type="checkbox" :value="item.id" v-model="ids" />
                                </div>
                                <div class="col-xs-1">
                                    <span v-if="item.use_custom == '0' && item.post_type" class="ails-pt" :title="item.post_type">{{ truncate(item.post_type, 7) }}</span>
                                    <span v-else class="ails-pt"><?php echo __('Custom', 'automatic-internal-links-for-seo'); ?></span>
                                </div>
                                <div class="col-xs">
                                    <a href="#" @click.prevent="findItem(item.id)">
                                        {{ truncate(item.title, 40) }}
                                    </a>
                                </div>
                                <div class="col-xs-3">
                                    <span class="ails-keyword">
                                        {{ truncate(item.keyword, 30) }}
                                    </span>
                                </div>
                                <div class="col-xs-1" style="text-align:center;">
                                    {{ item.priority }}
                                </div>
                                <div class="col-xs-1">
                                    <label class="ails-toggle"><input type="checkbox" name="status" v-model="item.status" @change="updateStatus(item.id)" /><span class='ails-toggle-slider ails-toggle-round'></span></label>
                                </div>
                                <div class="col-xs-1">
                                    <button class="ails-indigo" @click.prevent="findItem(item.id)"><?php echo __('Edit', 'automatic-internal-links-for-seo'); ?></button>
                                </div>
                                <div class="col-xs-1">
                                    <button class="ails-red" @click.prevent="deleteItem(item.id)"><?php echo __('Delete', 'automatic-internal-links-for-seo'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    </paginate>

                    <div class="row bewteen-xs" style="margin-top: 15px">
                        <div class="col-xs-12 col-md-9">
                            <paginate-links @change="onPageChange" for="items" :limit="9" :show-step-links="true" ></paginate-links>
                        </div>
                        <div class="col-xs-12 col-md-3" style="margin-top: 10px; text-align:right; padding-right: 15px">
                            <span v-if="$refs.paginator"><?php echo __('Viewing', 'automatic-internal-links-for-seo'); ?> {{$refs.paginator.pageItemsCount}} <?php echo __('results', 'automatic-internal-links-for-seo'); ?></span>
                        </div>
                    </div>

                </div>

                <div v-else>
                    <p style="text-align: center; font-size: 16px; font-weight: bold"><?php echo __('No links found.', 'automatic-internal-links-for-seo'); ?></p>
                </div>

            </div>

        </div>

        <transition name="ails-slide-fade">

        <div v-if="showEdit" class="col-xs-12 col-md-4 col-lg-3">

            <?php include "inc/edit.view.php"; ?>

        </div>

        </transition>

    </div>

</div>