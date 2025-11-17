<?php
/**
 * Plugin Name:       Brazillian Journal | Funcionalidades
 * Plugin URI:        https://patropicomunica.com.br
 * Description:       Funcionalidades para o portal de artigos funcionar. 
 * Version:           0.0.6
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
	require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
}
add_action( 'plugins_loaded', 'patropi_bjo_check_dependencies' );

/**
 * Verifica se as taxonomias 'autor' e 'journal' existem.
 * Se não existirem, exibe um aviso no painel administrativo.
 */
function patropi_bjo_check_required_taxonomies() {
	$missing_taxonomies = [];

	if ( ! patropi_bjo_is_taxonomy_active( 'autor' ) ) {
		$missing_taxonomies[] = 'autor';
	}

	if ( ! patropi_bjo_is_taxonomy_active( 'journal' ) ) {
		$missing_taxonomies[] = 'journal';
	}

	if ( ! empty( $missing_taxonomies ) ) {
		add_action(
			'admin_notices',
			function() use ( $missing_taxonomies ) {
				$tax_list = implode( ' e ', array_map( 'esc_html', $missing_taxonomies ) );
				?>
				<div class="notice notice-error is-dismissible">
					<p>
						<strong>Atenção:</strong> A(s) taxonomia(s) <strong><?php echo $tax_list; ?></strong> não foi/foram encontrada(s) ou está/estão inativa(s). 
						Por favor, importe o arquivo JSON do Advanced Custom Fields (ACF) para garantir o funcionamento correto do site.
					</p>
				</div>
				<?php
			}
		);
	}
}
add_action( 'admin_init', 'patropi_bjo_check_required_taxonomies' );

/**
 * Verifica se uma taxonomia específica está registrada e se sua definição no ACF está ativa.
 *
 * @param string $taxonomy_slug O slug da taxonomia a ser verificada.
 * @return bool True se a taxonomia estiver ativa, false caso contrário.
 */
function patropi_bjo_is_taxonomy_active( $taxonomy_slug ) {
	// Passo 1: Verifica se a taxonomia foi registrada no WordPress.
	if ( ! taxonomy_exists( $taxonomy_slug ) ) {
		return false;
	}
	
	// Passo 2: Encontra a definição da taxonomia no ACF e verifica seu status.
	// As definições são salvas como posts do tipo 'acf-taxonomy'.
	$args = array(
		'post_type'      => 'acf-taxonomy',
		'posts_per_page' => -1,
		'post_status'    => 'any', // Busca em todos os status (ativo, inativo, lixeira).
	);
	
	$acf_taxonomies = get_posts( $args );
	$is_active = null;
	
	foreach ( $acf_taxonomies as $post ) {
		// As configurações são salvas serializadas no post_content.
		$config = maybe_unserialize( $post->post_content );
		if ( isset( $config['taxonomy'] ) && $config['taxonomy'] === $taxonomy_slug ) {
			// O status de ativação está dentro da configuração, não no post_status.
			// 'active' => 1 (Ativa), 'active' => 0 (Inativa).
			$is_active = true;
			break; // Encontramos a taxonomia correta, podemos parar o loop.
		}
	}
	
	// Se encontramos a definição no ACF, retornamos o seu status de ativação.
	if ( null !== $is_active ) {
		return $is_active;
	}

	// Se a taxonomia existe, mas não foi encontrada no ACF (pode ter sido criada por código),
	// consideramos como ativa para este contexto.
	return true;
}


/**
 * Redireciona a página de registro padrão do WP
 * para a nossa página de cadastro customizada do Elementor.
 */
function meu_tema_redirecionar_pagina_registro() {
    
    // Verifique se estamos na página wp-login.php E se a ação é 'register'
    if ( 'wp-login.php' === $GLOBALS['pagenow'] && isset( $_GET['action'] ) && 'register' === $_GET['action'] ) {
        
        // Substitua '/cadastro/' pela URL (slug) da sua página
        $nova_url_registro = home_url( '/cadastro/' ); 
        
        wp_redirect( $nova_url_registro );
        exit;
    }
}
add_action( 'init', 'meu_tema_redirecionar_pagina_registro' );

/**
 * Redireciona a página de login padrão do WP (wp-login.php)
 * para a nossa página de login customizada do Elementor.
 */
function meu_tema_redirecionar_pagina_login() {
    
    // Ignora o redirecionamento se for uma ação de logout ou se já estivermos no wp-admin
    if ( ( isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) || is_admin() ) {
        return;
    }

    global $pagenow;
    
    // Se estivermos tentando acessar a wp-login.php diretamente (sem ação)
    if ( 'wp-login.php' == $pagenow && empty( $_GET['action'] ) ) {
        
        // Substitua '/login/' pela URL (slug) da sua página
        $nova_url_login = home_url( '/login/' ); 
        
        wp_redirect( $nova_url_login );
        exit;
    }
}
add_action( 'init', 'meu_tema_redirecionar_pagina_login' );	