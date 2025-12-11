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
	if ( ! ( ( $query->is_home() || is_page( 'artigos' ) ) && $query->is_main_query() ) ) {
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

/**
 * Obtém variações do termo de busca em PT e EN usando Google Translate API.
 *
 * @param string $term O termo original.
 * @return array Array com o termo original e traduções.
 */
function patropi_bjo_get_search_variations( $term ) {
	// 1. Tenta obter a chave do banco de dados (salva na página de configs).
	$api_key = get_option( 'patropi_bjo_google_translate_key' );

	// 2. Fallback: Se não houver no banco, verifica se a constante antiga existe.
	if ( empty( $api_key ) && defined( 'BJO_GOOGLE_TRANSLATE_API_KEY' ) ) {
		$api_key = BJO_GOOGLE_TRANSLATE_API_KEY;
	} 

	if ( empty( $api_key ) || empty( $term ) ) {
		return array( $term );
	}

	$transient_key = 'bjo_tr_' . md5( $term );
	$cached_terms  = get_transient( $transient_key );

	if ( false !== $cached_terms ) {
		return $cached_terms;
	}

	$variations = array( $term );
	$targets    = array( 'pt', 'en' );

	foreach ( $targets as $lang ) {
		$url      = "https://translation.googleapis.com/language/translate/v2?key={$api_key}&q=" . urlencode( $term ) . "&target={$lang}";
		$response = wp_remote_get( $url );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['data']['translations'][0]['translatedText'] ) ) {
				$variations[] = $body['data']['translations'][0]['translatedText'];
			}
		}
	}

	$variations = array_unique( $variations );
	set_transient( $transient_key, $variations, DAY_IN_SECONDS ); // Cache por 1 dia.

	return $variations;
}

function bjo_add_custom_search_where( $clauses, $query ) {
	$search_term = $query->get( 'bjo_custom_search' );

	if ( ! empty( $search_term ) ) {
		global $wpdb;

		// Garante que não teremos posts duplicados se o termo for encontrado em múltiplos campos.
		$clauses['distinct'] = 'DISTINCT';

		// Junta a tabela de metadados para que possamos pesquisar nos campos do ACF.
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS pm ON {$wpdb->posts}.ID = pm.post_id";

		// Obtém variações de idioma (PT/EN).
		$search_terms_variations = patropi_bjo_get_search_variations( $search_term );

		// Pega os escopos da busca da URL. Se não houver, usa todos como padrão.
		$default_scopes = [ 'post_title', 'artigo_body', 'abstract_html', 'artigo_doi' ];
		$selected_scopes = $_GET['search_scope'] ?? $default_scopes;

		$search_conditions = [];
		$prepare_args = [];

		foreach ( $search_terms_variations as $term ) {
			$like = '%' . $wpdb->esc_like( $term ) . '%';

			// Constrói as condições dinamicamente com base nos escopos selecionados.
			if ( in_array( 'post_title', $selected_scopes, true ) ) {
				$search_conditions[] = "{$wpdb->posts}.post_title LIKE %s";
				$prepare_args[]      = $like;
				// Inclui os novos campos de título traduzidos.
				$search_conditions[] = "(pm.meta_key = 'titulo_artigo_pt' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
				$search_conditions[] = "(pm.meta_key = 'titulo_artigo_en' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
			}
			if ( in_array( 'artigo_body', $selected_scopes, true ) ) {
				$search_conditions[] = "(pm.meta_key = 'artigo_body' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
			}
			if ( in_array( 'abstract_html', $selected_scopes, true ) ) {
				// Inclui os novos campos de resumo traduzidos.
				$search_conditions[] = "(pm.meta_key = 'abstract_html_pt' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
				$search_conditions[] = "(pm.meta_key = 'abstract_html_en' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
			}
			if ( in_array( 'artigo_doi', $selected_scopes, true ) ) {
				$search_conditions[] = "(pm.meta_key = 'artigo_doi' AND pm.meta_value LIKE %s)";
				$prepare_args[]      = $like;
			}
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