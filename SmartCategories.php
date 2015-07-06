<?php
/*
Plugin Name: Smart Categories
Description: Automatically sorts all / new posts into categories based on title content
Author: Rob Pannell
Version: 1.0.3
Author URI: http://robpannell.com/
*/

function SmartCatAddPage() {
	add_menu_page('Smart Categories', 'Smart Categories', 'edit_posts', 'smart-categories', 'SmartCatAdminPage','dashicons-category');
}
add_action('admin_menu', 'SmartCatAddPage');

add_option('AutoCatActive', 'active');
add_option('AutoCatRuleIDs','1');
add_option('AutoCatRule1Cat','1');
add_option('AutoCatRule1Phrase','Example Rule');
add_option('AutoCatLastSorted', '');

if(get_option('AutoCatActive') == 'active'){ add_action( 'get_header', 'SmartCatPageLoadUpdate' ); }

function SmartCatAdminPage() {
    if(isset($_POST['updateRules'])){
        $message = SmartCatUpdateRules();
    }
    if(isset($_POST['updateAllPosts'])){
        $message = SmartCatCategorisePosts();
    }
    if(isset($_POST['manualCheck'])) 
    { 
        $message = SmartCatCategorisePosts(get_option('AutoCatLastSorted'));
    }
    if(isset($_POST['stripCats'])){
        $message = SmartCatStripCategories();
    }
    if(isset($_POST['autoSort'])){ 
        update_option('AutoCatActive', $_POST['autoSort']); 
        $message = 'Auto preference updated.';
    }
    
    $acRules = SmartCatFetchRules();
    $newRuleID = SmartCatFindNextFreeRuleID($acRules);
    $categories = SmartCatFetchWordpressCats();
    $pageUrl = get_admin_url('admin.php').'?page=smart-categories';
    
    add_thickbox();
    ?>

<style> 
    #smartCats table { margin-bottom: 25px; } 
    #smartCats select { vertical-align: top; } 
    #smartCats table td select { width: 100%; }
    #smartCats table input[type=checkbox] { margin: 0 auto; display: block;}
</style>

<div id="smartCats" class="wrap" style="max-width: 1000px;">
        <h2><span class="dashicons dashicons-category" style="font-size: 1.2em; margin-right: 10px;"></span> Smart Categories</h2>
        <?php echo get_option('AutoCatActive');
        if($message) { ?>
            <div id="message" class="updated notice is-dismissible">
		<p><strong><?php echo $message;?></strong></p>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            </div>
        
        <?php } ?>
        <p>This plugin will automatically put posts into categories for you based on the post title containing a key word/phrase. By default, newly created posts are checked every time a page is loaded, however you can switch to switch it to only be fired manually. You can run the process manually using the buttons below.</p>
        <p>Last checked: <strong><?php echo date('G:i dS F Y', get_option('AutoCatLastSorted'));?></strong></p>
        <form method="post" action="<?php echo $pageUrl;?>">
            <p>
                <button name="manualCheck" type="submit" class="button-primary">Process New Posts</button>
                <button class="button-primary" name="updateAllPosts" type="submit">Process ALL Posts</button>
            </p>    
        </form>
        
        <hr />
                
        <h3>Auto / Manual Filtering</h3>
        <form name="FilterRule" method="post" action="<?php echo $pageUrl;?>">
            <blockquote>
                <p>
                <input name="autoSort" type="radio" value="active" onclick="this.form.submit()"
                       <?php if(get_option('AutoCatActive') == 'active') echo ' checked';?> />
                <label>Auto <a href="#TB_inline?width=400&height=100&inlineId=autoDef" class="thickbox">(?)</a> </label><br/>
                <input name="autoSort" type="radio" value="inactive" onclick="this.form.submit()"
                       <?php if(get_option('AutoCatActive') == 'inactive') echo ' checked';?> />
                <label>Manual <a href="#TB_inline?width=400&height=100&inlineId=manualDef" class="thickbox">(?)</a></label><br/>
                </p>
            </blockquote>
        </form>
        <div id="autoDef" style="display:none;">
            <p>Each time a user loads a page on your website, the plugin will check for any new posts that were added and apply your rules against them.</p>
        </div>
        <div id="manualDef" style="display:none;">
            <p>Nothing will happen automatically. You will need to click the 'Process New Posts' button on this admin page to process posts.</p>
        </div>
        
        <hr />
                
        <h3>Active Rules:</h3>
        <form method="post" action="<?php echo $pageUrl;?>">
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Post Title Contains</th>
                        <th>Destination Category</th>
                        <th style="text-align: center;">Delete Rule(s)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($acRules as $acRule){ ?>
                        <tr>
                            <td>
                                <input type="text" name="phrase-<?php echo $acRule['id'];?>" value="<?php echo stripslashes($acRule['phrase']);?>" style="width:100%;">
                            </td>
                            <td>
                                <?php wp_dropdown_categories(array('name'=>'cat-'.$acRule['id'], 'hide_empty' => 0, 'orderby' => 'name', 'hierarchical' => true, 'selected' => intval($acRule['cat']))); ?>
                            </td>
                            <td>
                                <input type="checkbox" name="delete-<?php echo $acRule['id'];?>" value="DELETE">
                            </td>
                        </tr>
                        <?php
                        if($rulesShown) { $rulesShown .= ','; }
                        $rulesShown .= $acRule['id'];
                    }
                    ?>
                    <tr>
                        <td><input name="phrase" type="text" placeholder="Add New Rule" style="width:100%;"></td>
                        <td><?php wp_dropdown_categories(array('name'=>'cat', 'hide_empty' => 0, 'orderby' => 'name', 'hierarchical' => true, 'selected' => $acRule['id'])); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" align="right"><button name="updateRules" value="<?php echo $rulesShown;?>" class="button-primary" type="submit">Save Changes</button></th>
                    </tr>
                </tfoot>
            </table>
        </form>
        
        <h3>Remove a category from existing posts: <a href="#TB_inline?width=400&height=100&inlineId=stripCat" class="thickbox">(?)</a></h3>
        <div id="stripCat" style="display:none;">
            <p>This is a one time operation. If you have deleted a rule, this tool gives you the option to undo the effects. Remember to check 
                the relevant rule is deleted else the categories will be reapplied next time the update process runs.</p>
        </div>
        <form name="stripCats" method="post" action="<?php echo $pageUrl;?>">    
            <p><input name="phrase" type="text" required style="width:40%;" placeholder="Enter key word/phrase">
               <?php wp_dropdown_categories(array('name'=>'cat', 'hide_empty' => 0, 'orderby' => 'name', 'hierarchical' => true)); ?>
               <button name="stripCats" class="button-primary" value="<?php echo $newRuleID;?>" type="submit">Process</button>
            </p>
        </form>
        
        
    </div>
    <?php 
}

function SmartCatFetchRules() {
    
    $rules = explode(',',  get_option('AutoCatRuleIDs'));
    
    foreach($rules as $rule){
        if($rule != ''){
            $acRules[] = array('id'=>$rule,'cat'=>get_option('AutoCatRule'.$rule.'Cat'), 'phrase'=>get_option('AutoCatRule'.$rule.'Phrase'));
        }
    }
    return $acRules;
}

function SmartCatFindNextFreeRuleID() {
    $newID = 1;
    $activeRules = explode(',', get_option('AutoCatRuleIDs'));
    while (in_array($newID, $activeRules)){
        $newID ++;
    }
    
    return $newID;
}
function SmartCatAddRule($id, $cat, $phrase){
    update_option('AutoCatRule'.$id.'Cat', $cat);
    update_option('AutoCatRule'.$id.'Phrase', addslashes($phrase));    
    if(get_option('AutoCatRuleIDs')){ $toAppend = ','; }
    $toAppend .= $id;
    update_option('AutoCatRuleIDs',get_option('AutoCatRuleIDs').$toAppend);
        
    return 'Your rule has been added'.SmartCatOfferUpdate();
}
function SmartCatUpdateRules(){
    $rulesToUpdate = explode(',', $_POST['updateRules']);
    foreach($rulesToUpdate as $rule){
          
        if($_POST['delete-'.$rule]) {
            delete_option('AutoCatRule'.$rule.'Cat');
            delete_option('AutoCatRule'.$rule.'Phrase');
            $rulesToDelete[] = $rule;
        }
        else if(get_option('AutoCatRule'.$rule.'Cat') != $_POST['cat-'.$rule] || get_option('AutoCatRule'.$rule.'Phrase') != $_POST['phrase-'.$rule]) {
            update_option('AutoCatRule'.$rule.'Cat', $_POST['cat-'.$rule]);
            update_option('AutoCatRule'.$rule.'Phrase', addslashes($_POST['phrase-'.$rule]));
            $rulesUpdated[] = $rule;
        }
    }
    if($rulesToDelete){ SmartCatDeleteRules($rulesToDelete); }
    
    if($_POST['phrase'] != '') {
        $newID = SmartCatFindNextFreeRuleID();
        SmartCatAddRule($newID, $_POST['cat'], $_POST['phrase']);
        $rulesUpdated[] = $newID;
    }

    $returnvalue = 'Your changes have been saved. '.count($rulesUpdated).' rules were updated, '.count($rulesToDelete).' rules deleted.';
    if(count($rulesUpdated) > 0){ $returnvalue .= SmartCatOfferUpdate(); }
    
    return $returnvalue;
}

function SmartCatDeleteRules($rulesToDelete = array('0')) {
    $activeRules = explode(',',get_option('AutoCatRuleIDs'));
    $newActiveRules = '';
    foreach ($activeRules as $activeRule){
        if(!in_array($activeRule, $rulesToDelete)){
            if($newActiveRules) 
            { 
                $newActiveRules .= ','; 
            }
            $newActiveRules .= $activeRule;
        }
        update_option('AutoCatRuleIDs',$newActiveRules);
    }
}

function SmartCatFetchWordpressCats() {
    
    $args = array('hide_empty'=>0,'orderby'=>'id');
    $wpCats = get_categories($args);
    
    return $wpCats;
}

function SmartCatCategorisePosts($newerThan = '149435'){
    
    $postsToProcess = SmartCatFetchWPPosts($newerThan);
    $acRules = SmartCatFetchRules();
    
    if($postsToProcess) {
        foreach($postsToProcess as $id) 
        {
            $title = get_the_title($id);
            foreach($acRules as $rule){
                if($title == $rule['phrase'] || stripos($title, $rule['phrase']) !== FALSE){
                    wp_set_post_categories($id,array($rule['cat']),true);
                    $updatedPosts[] = $id;
                }
            }
        }
        update_option('AutoCatLastSorted', time());
        return count($postsToProcess).' Posts processed, '.count($updatedPosts).' posts updated';
    }
    else {
        return 'No posts to check';
    }
}

function SmartCatFetchWPPosts($newerThan){
    
    $newerThan = date('dS F Y G:i', $newerThan);
    
    $args = array(
        'date_query' => array(array('after' => $newerThan)),
        'nopaging' => true,
        'posts_per_page' => -1,
        'fields' => 'ids'
        );
    $the_query = new WP_Query( $args );
    if(isset($the_query->posts) && !empty($the_query->posts)){
        return (array) $the_query->posts;
    }
    else { return NULL; }
}

function SmartCatPageLoadUpdate(){ 
        SmartCatCategorisePosts(get_option('AutoCatLastSorted'));
}

function SmartCatOfferUpdate() {
    return
    '<form method="post" action="'.admin_url('admin.php').'?page=smart-categories">'
        . '<button class="button-primary" name="updateAllPosts" type="submit">Apply your changes to historic posts</button>'
    . '</form>';
}

function SmartCatStripCategories($newerThan = '149435') {
    
    $postsToUpdate = SmartCatFetchWPPosts($newerThan);
    $postsFixed = 0;
    
    foreach ($postsToUpdate as $post){
        $title = get_the_title($post);
        if($title == $_POST['phrase'] || stripos($title, $_POST['phrase']) !== FALSE){
            $currentCats = wp_get_post_categories($post);
            if(($key = array_search($_POST['cat'], $currentCats)) !== false) { unset($currentCats[$key]); }
            wp_set_post_categories($post,$currentCats,false);
            $postsFixed ++;
        }
    }
    return 'The specified category was removed from '.$postsFixed.' posts.';
}