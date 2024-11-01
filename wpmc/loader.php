<?php
require_once __DIR__ . '/functions.php';

if ( !defined('WPMC_LOADED') ) {
    define('WPMC_LOADED', 1);

    add_action('admin_menu', function(){
        if ( !class_exists('WPMC_List_Table')) {
            require_once __DIR__ . '/WPMC_List_Table.php';
        }
        if ( !class_exists('WPMC_Entity')) {
            require_once __DIR__ . '/WPMC_Entity.php';
        }
        if ( !class_exists('WPMC_Form')) {
            require_once __DIR__ . '/WPMC_Form.php';
        }
        if ( !class_exists('WPMC_Field_Common')) {
            require_once __DIR__ . '/WPMC_Field_Common.php';
            $fieldCommon = new WPMC_Field_Common();
            $fieldCommon->initHooks();
        }
        if ( !class_exists('WPMC_Field_OneToMany')) {
            require_once __DIR__ . '/WPMC_Field_OneToMany.php';
            $fieldEntity = new WPMC_Field_OneToMany();
            $fieldEntity->initHooks();
        }
        if ( !class_exists('WPMC_Field_HasMany')) {
            require_once __DIR__ . '/WPMC_Field_HasMany.php';
            $fieldHasMany = new WPMC_Field_HasMany();
            $fieldHasMany->initHooks();
        }
        if ( !class_exists('WPMC_Field_BelongsTo')) {
            require_once __DIR__ . '/WPMC_Field_BelongsTo.php';
            $fieldBelongsTo = new WPMC_Field_BelongsTo();
            $fieldBelongsTo->initHooks();
        }
        if ( !class_exists('WPMC_Database')) {
            require_once __DIR__ . '/WPMC_Database.php';
        }
        if ( !class_exists('WPMC_Query_Builder')) {
            require_once __DIR__ . '/WPMC_Query_Builder.php';
        }
    
        $arrEntities = apply_filters('wpmc_entities', array());
        $entities = wpmc_load_app_entities($arrEntities);
    
        // create entities database structure on-the-fly
        if ( apply_filters('wpmc_run_create_tables', false) ) {
            $db = new WPMC_Database();
            $db->migrateEntityTables($entities);
    
            do_action('wpmc_after_create_tables');
        }
    
        // trigger entity menus
        foreach ( $entities as $entity ) {
            $entity->admin_menu();
            // $entity->init_hooks();
        }
    
        do_action('wpmc_loaded');
    }, 500);
    
    // admin styles
    add_action('admin_enqueue_scripts', function(){
        wp_enqueue_style('wpmc-styles', plugins_url('/wpmc/styles.css', dirname(__FILE__) ));
        wp_enqueue_script('wpmc-scripts', plugins_url('/wpmc/scripts.js', dirname(__FILE__)));
    });
}
