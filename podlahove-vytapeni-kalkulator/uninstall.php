<?php
/**
 * Uninstall script pro Kalkulátor podlahového vytápění
 * 
 * Tento soubor se spustí při úplném odstranění pluginu
 * Vymaže všechna nastavení a data z databáze
 */

// Zabránit přímému přístupu
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Smazání nastavení pluginu
delete_option('pv_settings');

// Smazání transientů (pokud by plugin používal cache)
delete_transient('pv_calculation_cache');

// Smazání meta hodnot (pokud by plugin ukládal data k příspěvkům)
delete_post_meta_by_key('pv_calculation_data');

// Smazání user meta (pokud by plugin ukládal data k uživatelům)
delete_metadata('user', 0, 'pv_user_calculations', '', true);

// Vyčištění databáze od vlastních tabulek (pokud by plugin vytvářel tabulky)
global $wpdb;

// Příklad: $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pv_calculations");

// Log odstranění pro debug účely (volitelné)
error_log('Plugin Kalkulátor podlahového vytápění byl úspěšně odstraněn včetně všech dat.');

// Vyčištění opcache pokud je dostupné
if (function_exists('opcache_reset')) {
    opcache_reset();
}