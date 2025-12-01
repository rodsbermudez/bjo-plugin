<?php
/**
 * Plugin Name:       Brazillian Journal | Funcionalidades
 * Plugin URI:        https://patropicomunica.com.br
 * Description:       Funcionalidades para o portal de artigos funcionar. 
 * Version:           0.0.12
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Rodrigo Bermudez | Patropi Comunica
 * Author URI:        https://patropicomunica.com.br
 * Text Domain:       patropi-bjo
 * Domain Path:       /languages
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} 

// A função de configuração precisa ser definida aqui para estar disponível globalmente.
if ( ! function_exists( 'patropi_bjo_get_n8n_config' ) ) {
	/**
	 * Carrega e retorna as configurações de integração do N8N.
	 *
	 * @return array As configurações do arquivo n8n.config.php.
	 */
	function patropi_bjo_get_n8n_config() {
		$config_file = plugin_dir_path( __FILE__ ) . 'includes/n8n.config.php';
		if ( ! file_exists( $config_file ) ) {
			return array(
				'api_key'  => '',
				'webhooks' => [],
			);
		}

		return include $config_file;
	}
} 

// Carrega todos os arquivos de funcionalidades do plugin.
// Fazer isso no escopo global garante que todas as funções e classes
// estarão disponíveis para os hooks do WordPress.
require_once plugin_dir_path( __FILE__ ) . 'includes/visitor-tracking.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/n8n-integration.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/article-views-tracking.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/importer.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/assets.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/taxonomias.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/queries.php';


/**
 * Função principal executada na ativação do plugin.
 * Chama todas as rotinas de setup necessárias.
 */
function patropi_bjo_activate_plugin() {
	// Carrega os arquivos necessários para a ativação, garantindo que as funções existam.

	patropi_bjo_create_visitor_table();
	patropi_bjo_create_n8n_log_table();
	patropi_bjo_create_article_views_table();

	// Força a atualização das regras de reescrita para aplicar o novo slug da categoria.
	flush_rewrite_rules();
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

/**
 * Altera os rótulos e o slug da taxonomia de categoria nativa do WordPress.
 *
 * @param string $taxonomy O nome da taxonomia sendo registrada.
 * @param string|array $object_type O(s) tipo(s) de objeto associado(s).
 * @param array        $args       Argumentos da taxonomia.
 * @return array                   Argumentos modificados.
 */
function bjo_change_category_args( $args, $taxonomy, $object_type ) {
	// Altera apenas a taxonomia 'category'.
	if ( 'category' !== $taxonomy ) {
		return $args;
	} 

	// Altera os rótulos para "Áreas de Atuação".
	$labels = array(
		'name'              => 'Áreas de Atuação',
		'singular_name'     => 'Área de Atuação',
		'search_items'      => 'Pesquisar Áreas de Atuação',
		'all_items'         => 'Todas as Áreas de Atuação',
		'parent_item'       => 'Área de Atuação Pai',
		'parent_item_colon' => 'Área de Atuação Pai:',
		'edit_item'         => 'Editar Área de Atuação',
		'update_item'       => 'Atualizar Área de Atuação',
		'add_new_item'      => 'Adicionar Nova Área de Atuação',
		'new_item_name'     => 'Novo Nome da Área de Atuação',
		'menu_name'         => 'Áreas de Atuação',
	);
	$args['labels'] = $labels;

	// Altera o slug da URL.
	$args['rewrite']['slug'] = 'area-de-atuacao';

	return $args;
}
add_filter( 'register_taxonomy_args', 'bjo_change_category_args', 10, 3 );

/**
 * Modifica a query de posts do Elementor para aplicar os filtros de taxonomia da URL.
 *
 * @param \WP_Query $query A instância da query do WordPress.
 */
function bjo_handle_article_filters_query( $query ) {
    // Mapeia os parâmetros da URL para as taxonomias corretas.
    $tax_map = [
        'filter_area_de_atuacao' => 'category',
        'filter_autor'           => 'autor',
        'filter_journal'         => 'journal',
        'filter_palavra_chave'   => 'palavra-chave',
        'filter_tipo_do_artigo'  => 'tipo-do-artigo',
    ];

    $tax_query = ['relation' => 'AND']; // Requer que o post corresponda a TODAS as taxonomias filtradas.

    $is_filter_active = false;

    foreach ( $tax_map as $param => $taxonomy ) {
        // Verifica se o parâmetro existe na URL, não está vazio e é um array.
        if ( isset( $_GET[ $param ] ) && is_array( $_GET[ $param ] ) && ! empty( $_GET[ $param ] ) ) {
            
            $term_ids = array_map( 'intval', $_GET[ $param ] ); // Garante que são IDs numéricos.

            if ( ! empty( $term_ids ) ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                    'operator' => 'IN', // O post pode ter QUALQUER um dos termos selecionados para esta taxonomia.
                ];
                $is_filter_active = true;
            }
        }
    }

    // Só modifica a query se pelo menos um filtro estiver ativo.
    if ( $is_filter_active ) {
        $current_tax_query = $query->get( 'tax_query' );
        if ( ! is_array( $current_tax_query ) ) {
            $current_tax_query = [];
        }

        // Combina a nossa query de filtro com qualquer query existente.
        $query->set( 'tax_query', array_merge( $current_tax_query, $tax_query ) );
    }
}
// O nome 'filtro_artigos' deve ser o mesmo usado no campo "Query ID" do widget Posts do Elementor.
add_action( 'elementor/query/filtro_artigos', 'bjo_handle_article_filters_query' );

/**
 * Processa a submissão do formulário para salvar o ambiente do N8N.
 */
function patropi_bjo_handle_save_n8n_environment() {
	// 1. Verificações de segurança.
	if ( ! isset( $_POST['patropi_bjo_n8n_env_nonce'] ) || ! wp_verify_nonce( $_POST['patropi_bjo_n8n_env_nonce'], 'patropi_bjo_save_n8n_env' ) ) {
		wp_die( 'Falha na verificação de segurança.' );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Você não tem permissão para realizar esta ação.' );
	}
  
	// 2. Salva a opção no banco de dados.
	$environment = isset( $_POST['n8n_global_environment'] ) && 'test' === $_POST['n8n_global_environment'] ? 'test' : 'production';
	update_option( 'patropi_bjo_n8n_environment', $environment );

	wp_safe_redirect( admin_url( 'admin.php?page=patropi-bjo-imports&settings-saved=true' ) );
	exit;
}
add_action( 'admin_post_patropi_bjo_save_n8n_environment', 'patropi_bjo_handle_save_n8n_environment' );

/**
 * Verifica o status de um webhook fazendo uma requisição GET.
 *
 * @param string $url     A URL do webhook a ser verificada.
 * @param string $api_key A chave de API para autenticação.
 * @return bool True se o status for 200 ou 400 (indicando que o endpoint está ativo), false caso contrário.
 */
function patropi_bjo_check_webhook_status( $url, $api_key ) { 
	if ( empty( $url ) ) {
		return false; 
	}

	// Usa um timeout curto para não atrasar o carregamento da página.
	// Usamos uma requisição POST sem corpo para verificar se o endpoint está ativo.
	$args = array(
		'timeout' => 5,
		'headers' => array( 'X-API-KEY' => $api_key ),
	);
	$response = wp_remote_post( $url, $args );

	if ( is_wp_error( $response ) ) {
		return;
	}

	$http_code = wp_remote_retrieve_response_code( $response );

	// Para um POST de verificação, um código 200 indica que o endpoint está ativo e acessível.
	return ( 200 === $http_code );
}