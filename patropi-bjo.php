<?php
/**
 * Plugin Name:       Brazillian Journal | Funcionalidades
 * Plugin URI:        https://patropicomunica.com.br
 * Description:       Funcionalidades para o portal de artigos funcionar.
 * Version:           0.0.5
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Rodrigo Mermudez | Patropi Comunica
 * Author URI:        https://patropicomunica.com.br
 * Text Domain:       patropi-bjo
 * Domain Path:       /languages
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} 


/**
 * Função principal executada na ativação do plugin.
 * Chama todas as rotinas de setup necessárias.
 */
function patropi_bjo_activate_plugin() {
	// Carrega os arquivos necessários para a ativação, garantindo que as funções existam.
	require_once plugin_dir_path( __FILE__ ) . 'includes/visitor-tracking.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/n8n-integration.php';

	patropi_bjo_create_visitor_table();
	patropi_bjo_create_n8n_log_table();
}
register_activation_hook( __FILE__, 'patropi_bjo_activate_plugin' );

/**
 * Verifica se o plugin "ACF to REST API" está ativo.
 * Se não estiver, desativa este plugin e mostra um aviso.
 */
function patropi_bjo_check_dependencies() {
	// Inclui o arquivo necessário para a função is_plugin_active().
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Define o caminho do plugin obrigatório.
	$required_plugin = 'acf-to-rest-api/class-acf-to-rest-api.php';

	if ( ! is_plugin_active( $required_plugin ) ) {
		// Desativa o nosso plugin.
		deactivate_plugins( plugin_basename( __FILE__ ) );

		// Mostra um aviso no painel administrativo.
		add_action( 
			'admin_notices',
			function() {
				echo '<div class="notice notice-error"><p>O plugin <strong>Brazillian Journal | Funcionalidades</strong> foi desativado. É necessário que o plugin <strong>ACF to REST API</strong> esteja instalado e ativo.</p></div>';
			}
		);
		return; // Interrompe a execução se a dependência não for atendida.
	}

	// Se as dependências estiverem OK, carrega o restante dos arquivos.
	require_once plugin_dir_path( __FILE__ ) . 'includes/visitor-tracking.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/n8n-integration.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/importer.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/importer.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
}
add_action( 'plugins_loaded', 'patropi_bjo_check_dependencies' );
