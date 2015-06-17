<?php

    //if uninstall not called from WordPress exit
    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
        exit();
    
    $rules = explode(',',  get_option('AutoCatRuleIDs'));
    foreach ($rules as $rule){
        delete_option('AutoCatRule'.$rule.'Phrase');
        delete_option('AutoCatRule'.$rule.'Cat');
    }
    
    delete_option('AutoCatActive');
    delete_option('AutoCatRuleIDs');
    delete_option('AutoCatRule1Cat');
    delete_option('AutoCatRule1Phrase');
    delete_option('AutoCatLastSorted');