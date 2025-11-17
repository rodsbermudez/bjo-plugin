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