<?php
/**
 * Funcionalidades de integração com o N8N.
 *
 * @package PatropiBJO
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Cria a tabela de log para posts criados via N8N na ativação do plugin.
 */
function patropi_bjo_create_n8n_log_table() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'patropi_n8n_log';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		log_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		post_id bigint(20) unsigned NOT NULL,
		post_title text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	// Inclui o arquivo necessário para a função dbDelta().
	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}
	// Executa a query.
	dbDelta( $sql );
}

// Verifique se a função já não foi declarada (evita erro fatal em recarregamentos)
if ( ! function_exists( 'n8n_navigator_save_acf_on_journal_create' ) ) {

	/**
	 * Salva os dados do ACF enviados pelo N8N *depois* que um termo 'journal' é criado via API REST.
	 * Este é o 'hook' que escuta o endpoint /wp/v2/journal
	 */
	function n8n_navigator_save_acf_on_journal_create( $term, $request, $creating ) {

		// Só execute se for uma *nova* criação de termo
		if ( ! $creating ) {
			return;
		}

		// Verifique se a função 'update_field' do ACF existe. Se não, saia.
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		$params = $request->get_params();

		if ( ! isset( $params['acf'] ) || ! is_array( $params['acf'] ) ) {
			return;
		}

		$term_id = $term->term_id;
		foreach ( $params['acf'] as $key => $data ) {
			update_field( $key, $data, "journal_{$term_id}" );
		}
	}

	// Este é o gancho (hook) correto: 'rest_insert_{slug_da_taxonomia}'
	add_action( 'rest_insert_journal', 'n8n_navigator_save_acf_on_journal_create', 10, 3 );
}

/**
 * Registra um log quando um novo post é criado via API REST.
 *
 * @param WP_Post         $post     O objeto do post inserido.
 * @param WP_REST_Request $request  A requisição que criou o post.
 * @param bool            $creating True se for uma criação, false se for uma atualização.
 */
function patropi_bjo_log_n8n_post_creation( $post, $request, $creating ) {
	// Só execute se for uma *nova* criação de post.
	if ( ! $creating ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'patropi_n8n_log';

	$wpdb->insert(
		$table_name,
		array(
			'log_time'   => current_time( 'mysql' ),
			'post_id'    => $post->ID,
			'post_title' => $post->post_title,
		)
	);
}
// "Engata" a função de log para ser executada após um 'post' ser inserido via API REST.
// Se o seu post type for outro, altere 'post' para o slug correto (ex: 'artigo').
add_action( 'rest_after_insert_post', 'patropi_bjo_log_n8n_post_creation', 10, 3 );

/**
 * Cria um shortcode para enviar as referências do artigo para o N8N.
 *
 * Uso: [enviar_referencias_n8n]
 * O shortcode deve ser usado dentro da página de um artigo.
 * Ele busca o campo 'referencias_do_artigo' e o envia para um webhook do N8N.
 *
 * @return string Mensagem de status da operação.
 */
function patropi_bjo_send_references_shortcode() {
	// 1. Verifica se estamos em uma página de post e se o ACF está ativo.
	if ( ! is_singular( 'post' ) || ! function_exists( 'get_field' ) || ! in_the_loop() ) {
		// Retorna um comentário HTML para não poluir o front-end se o shortcode for usado no lugar errado.
		return '<!-- Shortcode [enviar_referencias_n8n] deve ser usado dentro do loop de uma página de artigo. -->';
	}

	// 2. Obtém o ID do post e o valor do campo 'referencias_do_artigo'.
	$post_id           = get_the_ID();
	$artigo_doi = get_field( 'artigo_doi', $post_id ); 


	// 3. Se o campo de referências estiver vazio, retorna uma mensagem informativa.
	if ( empty( $artigo_doi ) ) {
		return '<p class="patropi-n8n-notice notice-warning">O campo de referências deste artigo não foi encontrado.</p>';
	}

	// 4. Prepara os dados para o envio.
	$webhook_url = '';
	$api_key     = '';
	// Pega o ambiente global salvo no banco de dados (padrão: 'production').
	$current_env = get_option( 'patropi_bjo_n8n_environment', 'production' );

	if ( function_exists( 'patropi_bjo_get_n8n_config' ) ) {
		$n8n_config = patropi_bjo_get_n8n_config();

		// Pega a URL e a chave de API do arquivo de configuração.
		$webhook_url = $n8n_config['webhooks']['citations'][ $current_env ] ?? '';
		$api_key     = $n8n_config['api_key'] ?? '';
	} 
 
	$data = array(
		'post_id'             => $post_id,
		'artigo_doi' => $artigo_doi,
	);

	$args = array(
		'body'    => wp_json_encode( $data ),
		'headers' => array(
			'Content-Type' => 'application/json',
			'X-API-KEY'    => $api_key,
		),
		'timeout' => 30,
	);

	// 5. Envia os dados e processa a resposta.
	$response = wp_remote_post( $webhook_url, $args );

	if ( is_wp_error( $response ) ) {
		$error_string = $response->get_error_message();
		error_log( 'Erro ao enviar referências do artigo para N8N: ' . $error_string );
		$error_message = '<p class="patropi-n8n-notice notice-error">Ocorreu um erro de conexão ao tentar enviar as referências. Tente novamente mais tarde.</p>';
		if ( current_user_can( 'manage_options' ) ) {
			$error_message .= '<p class="patropi-n8n-debug-info"><strong>Erro (admin):</strong> ' . esc_html( $error_string ) . '</p>';
			$error_message .= '<p class="patropi-n8n-debug-info"><strong>URL Tentada (admin):</strong> <code>' . esc_html( $webhook_url ) . '</code></p>';
		}
		return $error_message;
	}

	$http_code     = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	if ( $http_code >= 200 && $http_code < 300 ) {
		$success_message = '<p class="patropi-n8n-notice notice-success">Referências enviadas para processamento com sucesso!</p>';

		// Mostra a resposta JSON apenas para administradores.
		if ( current_user_can( 'manage_options' ) && ! empty( $response_body ) ) { 
			$citation_data = json_decode( $response_body, true );

			// Verifica se o JSON foi decodificado corretamente e se os dados esperados existem.
			if ( json_last_error() === JSON_ERROR_NONE && isset( $citation_data['citationCount'], $citation_data['data'] ) ) {
				
				$success_message .= '<div class="patropi-n8n-citations-result">';
				$success_message .= '<h3>Encontradas ' . esc_html( $citation_data['citationCount'] ) . ' citações:</h3>';

				if ( ! empty( $citation_data['data'] ) ) {
					$success_message .= '<ul class="lista-citacoes-n8n">';
					foreach ( $citation_data['data'] as $citation ) {
						$title = $citation['citingPaper']['title'] ?? 'Título não disponível';
						$doi   = $citation['citingPaper']['externalIds']['DOI'] ?? null;

						$success_message .= '<li>';
						$success_message .= '<span class="citation-title">' . esc_html( $title ) . '</span>';
						if ( $doi ) {
							$success_message .= '<a href="https://doi.org/' . esc_attr( $doi ) . '" target="_blank" rel="noopener noreferrer" class="button button-secondary">Ler artigo</a>';
						}
						$success_message .= '</li>';
					}
					$success_message .= '</ul>';
				}
				$success_message .= '</div>';
			} else {
				$success_message .= '<p class="patropi-n8n-debug-info"><strong>Resposta do servidor (visível para admin):</strong><pre style="white-space: pre-wrap; background: #f1f1f1; padding: 10px; border: 1px solid #ccc;">' . esc_html( $response_body ) . '</pre></p>';
			}
		}

		return $success_message;
	} else {
		error_log( "Erro ao enviar referências para N8N. Código: {$http_code}. Post ID: {$post_id}. Resposta: " . $response_body );
		
		$error_message = '<p class="patropi-n8n-notice notice-error">Falha na comunicação com o serviço de processamento. Tente novamente.</p>';

		// Mostra detalhes do erro apenas para administradores.
		if ( current_user_can( 'manage_options' ) ) {
			$error_message .= '<p class="patropi-n8n-debug-info"><strong>Detalhes (visível para admin):</strong><br>Código HTTP: ' . esc_html( $http_code ) . '<br>Resposta do servidor: <pre style="white-space: pre-wrap; background: #f1f1f1; padding: 10px; border: 1px solid #ccc;">' . esc_html( $response_body ) . '</pre></p>';
		}
		return $error_message;
	}
}
add_shortcode( 'enviar_referencias_n8n', 'patropi_bjo_send_references_shortcode' );