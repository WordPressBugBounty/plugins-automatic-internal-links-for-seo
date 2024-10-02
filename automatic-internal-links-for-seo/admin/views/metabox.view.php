<style>
.ails-toggle {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}
.ails-toggle input {
    display:none;
}
.ails-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ddd;
    -webkit-transition: .4s;
    transition: .4s;
}
.ails-toggle-slider:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 5px;
    bottom: 5px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
}
input:checked + .ails-toggle-slider {
    background-color: rgba(53,220,155,1);
}
input:focus + .ails-toggle-slider {
    box-shadow: 0 0 1px rgba(53,220,155,1);
}
input:checked + .ails-toggle-slider:before {
    -webkit-transform: translateX(26px);
    -ms-transform: translateX(26px);
    transform: translateX(26px);
}
.ails-item .ails-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 21px;
}
.ails-item .ails-toggle-slider:before {
    height: 13px;
    width: 16px;
    left: 4px;
    bottom: 4px;
}
.ails-item .ails-toggle-slider.ails-toggle-round,
.ails-item .ails-toggle-slider.ails-toggle-round:before {
    border-radius: 0;
}
.ails-label {
    display: inline-block;
    font-weight: 700;
    margin-right: 3px;
}
</style>
<div class="misc-pub-section misc-pub-section-last ails-container"><span id="timestamp">

    <label class="ails-label">Disable Auto Internal Links</label>
    <label class="ails-toggle"><input type="checkbox" name="disable_ails" value="disable_ails" <?php 
        if ( isset($disable_ails) && !empty($disable_ails) ) { echo  'checked' ;  }
    ?> /> /><span class='ails-toggle-slider ails-toggle-round'></span></label>

    <p><?php echo __('Checking this box will disable auto internal links for this page. Yoast Focus keyword will not be used to create auto internal links', 'automatic-internal-links-for-seo'); ?></p>

</div>
<?php 
$disable_autolinks = \Pagup\AutoLinks\Core\Option::get('disable_autolinks');

if ( empty($disable_ails || $disable_autolinks) ) { ?>
    <script type='text/javascript'>
    jQuery(document).ready(function(){
        jQuery("<div style='background-color: rgba(53,220,155,1); color: #fff; font-weight: bold; padding: 10px 10px 1px; margin-bottom: 5px;'>Focus keyphrase will be added to Auto Internal Link Logs. You can disable it from sidebar (for this page) or globally from <a href='admin.php?page=automatic-internal-links-settings' target='_blank' style='color: #fff!important'>Settings page</a></p>").insertBefore("#wpseo-metabox-root label:first-child");
    });
    </script>
<?php } ?>