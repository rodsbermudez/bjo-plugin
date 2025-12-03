<?php
/**
 * Funções para registrar e modificar queries customizadas do WordPress.
 *
 * @package BJO_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Modifica a query de artigos para aplicar filtros de taxonomia e busca por texto.
 *
 * Esta função é acionada antes da query ser executada, permitindo a adição de filtros.
 *
 * @param WP_Query $query O objeto da query do WordPress.
 */
function bjo_custom_query_filtro_artigos( $query ) {
	// CONDIÇÃO DE GUARDA: Garante que o código só rode na query correta.
	// - Não roda no admin.
	// - Roda na página 'artigos'.
	// - Afeta apenas a query principal da página (a lista de artigos).
	if ( is_admin() ) {
		return;
	}

	// Verifica se estamos na página de posts (is_home) ou numa página chamada 'artigos' E se é a query principal.
	if ( ( $query->is_home() || is_page( 'artigos' ) ) && $query->is_main_query() ) {
		return;
	}

	// 1. Lógica para filtros de taxonomia.
	$tax_query = array( 'relation' => 'AND' );
	foreach ( $_GET as $key => $value ) {
		if ( strpos( $key, 'filter_' ) === 0 && ! empty( $value ) ) {
			$taxonomy_slug = str_replace( [ 'filter_', '_' ], [ '', '-' ], $key );
			$taxonomy_name = ( $taxonomy_slug === 'area-de-atuacao' ) ? 'category' : $taxonomy_slug;

			$tax_query[] = [
				'taxonomy' => $taxonomy_name,
				'field'    => 'term_id',
				'terms'    => (array) $value,
				'operator' => 'IN',
			];
		}
	}

	if ( count( $tax_query ) > 1 ) {
		$query->set( 'tax_query', $tax_query );
	}

	// 2. Lógica para a busca por texto.
	if ( ! empty( $_GET['s_text'] ) ) {
		// Adicionamos um filtro que só será usado por esta query.
		$query->set( 'bjo_custom_search', sanitize_text_field( $_GET['s_text'] ) );
		add_filter( 'posts_clauses', 'bjo_add_custom_search_where', 10, 2 );
	}

	// DEBUG: Mostra os argumentos da query que serão usados.
    if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_sql'] ) ) {
		echo '<pre style="background: #fff; color: #333; border: 2px solid blue; padding: 15px; margin: 20px 0; z-index: 9999; position: relative; text-align: left; font-size: 14px; white-space: pre-wrap; word-wrap: break-word;">';
		echo '<strong>DEBUG: Query Loop Arguments</strong><br><br>';
		print_r( $query->query_vars );
		echo '</pre>';
	}
 }
 
 /**
  * Adiciona o hook 'pre_get_posts' no momento certo.
  *
  * As taxonomias customizadas são registradas no hook 'init'. Precisamos garantir
  * que nosso filtro de query seja adicionado DEPOIS disso, para que o WordPress
  * reconheça as taxonomias 'autor' e 'journal'.
  */
 function bjo_setup_query_filters() {
	 add_action( 'pre_get_posts', 'bjo_custom_query_filtro_artigos' );
 }
 add_action( 'init', 'bjo_setup_query_filters' );

function bjo_add_custom_search_where( $clauses, $query ) {
	$search_term = $query->get( 'bjo_custom_search' );

	if ( ! empty( $search_term ) ) {
		global $wpdb;

		// Garante que não teremos posts duplicados se o termo for encontrado em múltiplos campos.
		$clauses['distinct'] = 'DISTINCT';

		// Junta a tabela de metadados para que possamos pesquisar nos campos do ACF.
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id";

		$like = '%' . $wpdb->esc_like( $search_term ) . '%';

		// Pega os escopos da busca da URL. Se não houver, usa todos como padrão.
		$default_scopes = [ 'post_title', 'artigo_body', 'abstract_html', 'artigo_doi' ];
		$selected_scopes = $_GET['search_scope'] ?? $default_scopes;

		$search_conditions = [];
		$prepare_args = [];

		// Constrói as condições dinamicamente com base nos escopos selecionados.
		if ( in_array( 'post_title', $selected_scopes, true ) ) {
			$search_conditions[] = "{$wpdb->posts}.post_title LIKE %s";
			$prepare_args[] = $like;
		}
		if ( in_array( 'artigo_body', $selected_scopes, true ) ) {
			$search_conditions[] = "(pm.meta_key = 'artigo_body' AND pm.meta_value LIKE %s)";
			$prepare_args[] = $like;
		}
		if ( in_array( 'abstract_html', $selected_scopes, true ) ) {
			$search_conditions[] = "(pm.meta_key = 'abstract_html' AND pm.meta_value LIKE %s)";
			$prepare_args[] = $like;
		}
		if ( in_array( 'artigo_doi', $selected_scopes, true ) ) {
			$search_conditions[] = "(pm.meta_key = 'artigo_doi' AND pm.meta_value LIKE %s)";
			$prepare_args[] = $like;
		}

		// Se houver condições, adiciona à cláusula WHERE.
		if ( ! empty( $search_conditions ) ) {
			$where_clause = ' AND ( ' . implode( ' OR ', $search_conditions ) . ' )';

			// Adiciona a cláusula preparada à query principal.
			$clauses['where'] .= $wpdb->prepare( $where_clause, $prepare_args );
		}
	}

	// Remove o filtro para não afetar nenhuma outra query.
	remove_filter( 'posts_clauses', 'bjo_add_custom_search_where', 10, 2 );
	return $clauses;
}

/**
 * Adiciona o parâmetro 'query_id' aos parâmetros públicos de query do WordPress (ainda útil para outras funcionalidades).
 *
 * @param array $vars Array de variáveis de query públicas.
 * @return array Array modificado.
 */
function bjo_add_custom_query_vars( $vars ) {
	$vars[] = 'query_id';
	return $vars;
}
add_filter( 'query_vars', 'bjo_add_custom_query_vars' ); 