<?php
/**
 * Shortcodes for the BJO Plugin.
 *
 * @package BJO_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shortcode to display the references of an article.
 *
 * Usage: [bjo_referencias_artigo]
 * 
 * @return string HTML content for the references list.
 */
function bjo_referencias_artigo_shortcode() {
	// Ensure we are on a single post page and ACF is available.
	if ( ! is_singular() || ! function_exists( 'get_field' ) ) {
		return '<!-- Shortcode [bjo_referencias_artigo] is intended for single post pages with ACF plugin active. -->';
	}

	$post_id = get_the_ID();
	// Get the 'referencia' taxonomy terms for the post.
	$terms = get_the_terms( $post_id, 'referencia' );


	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p>Nenhuma referência encontrada para este artigo.</p>';
	}

	// Prepare an array to hold reference data for sorting.
	$referencias = array(); 
	foreach ( $terms as $term ) {
		$numero = get_field( 'numero_da_referencia', $term );
		$iso_format = get_field( 'referencia_formatada_iso', $term );
		$doi = get_field( 'referencia_doi', $term );

		if ( $numero && $iso_format ) {
			$referencias[] = array(
				'numero_da_referencia' => (int) $numero,
				'referencia_formatada_iso'   => $iso_format,
				'referencia_doi'    => $doi,
			);
		}
	}

	if ( empty( $referencias ) ) {
		return '<p>Nenhuma referência com os campos necessários encontrada.</p>';
	}

	// Sort references by 'numero_da_referencia'.
	usort( $referencias, function( $a, $b ) {
		$num_a = isset( $a['numero_da_referencia'] ) ? (int) $a['numero_da_referencia'] : 0;
		$num_b = isset( $b['numero_da_referencia'] ) ? (int) $b['numero_da_referencia'] : 0;
		return $num_a <=> $num_b;
	} );

	$output = '<div class="bjo-referencias-list">';
	$output .= '<h3>Referências</h3>';

	foreach ( $referencias as $ref ) {
		$numero = ! empty( $ref['numero_da_referencia'] ) ? esc_html( $ref['numero_da_referencia'] ) : null;
		$texto_referencia = ! empty( $ref['referencia_formatada_iso'] ) ? wp_kses_post( $ref['referencia_formatada_iso'] ) : 'Referência não disponível.';
		$doi = ! empty( $ref['referencia_doi'] ) ? esc_attr( $ref['referencia_doi'] ) : null;

		$output .= '<div class="bjo-referencia-item" style="margin-bottom: 1em;">';
		$output .= '<p>';

		if ( $numero ) {
			$output .= '<strong>' . $numero . '. </strong>';
		}

		$output .= $texto_referencia;

		if ( $doi ) {
			$output .= ' <a href="https://doi.org/' . $doi . '" target="_blank" rel="noopener noreferrer" class="button">Ver referência</a>';
		}

		$output .= '</p>';
		$output .= '</div>';
	}

	$output .= '</div>';

	return $output;
}
add_shortcode( 'bjo_referencias_artigo', 'bjo_referencias_artigo_shortcode' );

function bjo_artigo_info_shortcode() {
    // Certifique-se de que estamos em uma página de post.
    if (!is_singular('post') && !in_the_loop()) {
        return '<!-- Shortcode [bjo_artigo_info] só pode ser usado em páginas de artigo. -->';
    }

    $post_id = get_the_ID();
    ob_start(); // Inicia o buffer de saída para capturar o HTML.
    ?>
    <div class="bjo-artigo-info">

        <?php
        // 1. Listar Autores com links e ORCID
        $autores = get_the_terms($post_id, 'autor');
        if ($autores && !is_wp_error($autores)) {
            echo '<div class="autores-list" style="margin-bottom: 1.5em;">';
            $autores_html = array();
            foreach ($autores as $autor) {
                $autor_link = get_term_link($autor);
                $autor_orcid = get_field('autor_orcid', $autor); // Correct way to get term meta
                $autor_html = '<a href="' . esc_url($autor_link) . '">' . esc_html($autor->name) . '</a>';
                if ($autor_orcid) {
                    $autor_html .= ' <a href="https://orcid.org/' . esc_attr($autor_orcid) . '" target="_blank" class="orcid-link" title="ORCID">(ID)</a>';
                }
                $autores_html[] = $autor_html;
            }
            echo '<strong>Autores:</strong> ' . implode(', ', $autores_html);
            echo '</div>';
        }
        ?>

        <?php
        // 2. Informações do Journal
        $journals = get_the_terms($post_id, 'journal');
        if ($journals && !is_wp_error($journals)) {
            $journal = $journals[0]; // Pega o primeiro journal associado.
            $journal_title = get_field('journal-title', $journal);
            $journal_doi = get_field('journal-doi', $journal);
            $journal_e_issn = get_field('journal-e-issn', $journal);
            $journal_publisher = get_field('journal-publisher', $journal);
            $journal_endereco = get_field('journal-endereco-publisher', $journal);

            echo '<div class="journal-info" style="margin-bottom: 1.5em;">';
            echo '<h3>Informações do Journal</h3>';
            if ($journal_title) echo '<p><strong>Título:</strong> ' . esc_html($journal_title) . '</p>';
            if ($journal_doi) echo '<p><strong>DOI:</strong> ' . esc_html($journal_doi) . '</p>';
            if ($journal_e_issn) echo '<p><strong>e-ISSN:</strong> ' . esc_html($journal_e_issn) . '</p>';
            if ($journal_publisher) echo '<p><strong>Editora:</strong> ' . esc_html($journal_publisher) . '</p>';
            if ($journal_endereco) echo '<p><strong>Endereço da Editora:</strong> ' . esc_html($journal_endereco) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="artigo-meta" style="margin-bottom: 1.5em;">
            <h3>Detalhes do Artigo</h3>
            <?php
            // 3. DOI do Artigo
            $artigo_doi = get_field('artigo_doi', $post_id);
            if ($artigo_doi) {
                echo '<p><strong>DOI do Artigo:</strong> ' . esc_html($artigo_doi) . '</p>';
            }

            // 4. Publisher do Artigo
            $artigo_publisher = get_field('artigo_publisher_name', $post_id);
            if ($artigo_publisher) {
                echo '<p><strong>Editora do Artigo:</strong> ' . esc_html($artigo_publisher) . '</p>';
            }
            ?>
        </div> 

        <?php
        // 5. Botões de Download
        $url_pdf = get_field('url_do_pdf', $post_id);
        $url_xml = get_field('url_do_xml', $post_id);
        if ($url_pdf || $url_xml) {
            echo '<div class="artigo-downloads" style="margin-bottom: 1.5em;">';
            if ($url_pdf) {
                echo '<a href="' . esc_url($url_pdf) . '" class="button button-primary" target="_blank" download>Download PDF</a>';
            }
            if ($url_xml) {
                echo ' <a href="' . esc_url($url_xml) . '" class="button button-secondary" target="_blank" download>Download XML</a>';
            }
            echo '</div>';
        } 
        ?>
        
        <?php
        // 6. Abstract
        $abstract = get_field('abstract_html', $post_id);
        if ($abstract) {
            echo '<div class="artigo-abstract">';
            echo '<h3>Abstract</h3>';
            // O campo já é HTML, então usamos wp_kses_post para segurança.
            echo wp_kses_post($abstract);
            echo '</div>';
        }
        ?>

    </div>
    <?php
    return ob_get_clean(); // Retorna o conteúdo do buffer e o limpa.
}
add_shortcode('bjo_artigo_info', 'bjo_artigo_info_shortcode'); 

/**
 * Creates a shortcode to list authors of the current post.
 *
 * Usage: [bjo_listar_autores]
 *
 * @return string HTML output of the authors list.
 */
function bjo_listar_autores_shortcode() {
    if ( ! is_singular() || ! function_exists( 'get_field' ) ) {
        return '<!-- Shortcode [bjo_listar_autores] must be used on a single post page. -->';
    }

    $post_id = get_the_ID();
    $author_terms = get_the_terms( $post_id, 'autor' );

    if ( ! empty( $author_terms ) && ! is_wp_error( $author_terms ) ) {
        $authors_html = array();
        foreach ( $author_terms as $term ) {
            $author_name = esc_html( $term->name );
            $author_link = get_term_link( $term );
            $orcid = get_field( 'autor_orcid', 'term_' . $term->term_id );

            $author_string = '';
            if ( ! is_wp_error( $author_link ) ) {
                $author_string .= '<a href="' . esc_url( $author_link ) . '">' . $author_name . '</a>';
            } else {
                $author_string .= $author_name;
            }

            if ( $orcid ) {
                $orcid_url = 'https://orcid.org/' . esc_attr( $orcid );
                // The user asked for an ID icon, using text "[ID]" as a placeholder.
                $author_string .= ' <a href="' . esc_url( $orcid_url ) . '" target="_blank" rel="noopener noreferrer" title="ORCID de ' . esc_attr($author_name) . '">[ID]</a>';
            }
            
            $authors_html[] = $author_string;
        }
        return implode( ', ', $authors_html );
    }

    return '<!-- No authors found for this post. -->';
} 
add_shortcode( 'bjo_listar_autores', 'bjo_listar_autores_shortcode' );

/**
 * Shortcode para exibir um formulário de filtros de artigos por taxonomias.
 *
 * Usage: [bjo_filtros_artigos]
 *
 * @return string O HTML do formulário de filtros.
 */
function bjo_filtros_artigos_shortcode() {
    ob_start();

    // Define as taxonomias que queremos no filtro e seus rótulos.
    $taxonomies = [
        'area-de-atuacao' => 'Área de Atuação', // Usamos o slug que definimos
        'autor'           => 'Autor',           // Taxonomia customizada 'autor'
        'journal'         => 'Journal',         // Taxonomia customizada 'journal'
        'palavra-chave'   => 'Palavra-chave',   // Taxonomia nativa do WordPress para tags
        // 'tipo-do-artigo'  => 'Tipo do Artigo', // Removido/Comentado pois a taxonomia não existe
    ]; 

    // Pega os valores atuais do filtro da URL para manter os campos selecionados.
    $current_filters = [];
    foreach ( array_keys( $taxonomies ) as $tax_slug ) {
        if ( isset( $_GET[ 'filter_' . str_replace( '-', '_', $tax_slug ) ] ) ) {
            $current_filters[ $tax_slug ] = (array) $_GET[ 'filter_' . str_replace( '-', '_', $tax_slug ) ];
        }
    }
    ?>

    <div class="bjo-article-filters">
        <form role="search" method="get" class="bjo-filters-form" action="<?php echo esc_url( home_url( '/artigos/' ) ); ?>">
            
            <div class="filter-group">
                <label for="s_text" class="filter-label"><strong>Pesquisar por termo</strong></label>
                <input type="search" class="search-text-input" name="s_text" id="s_text" value="<?php echo esc_attr( $_GET['s_text'] ?? '' ); ?>" placeholder="Digite para buscar...">
                
                <div class="search-scope-group">
                    <label class="filter-label"><strong>Buscar em:</strong></label>
                    <?php
                    $search_scopes = [
                        'post_title'    => 'Título',
                        'artigo_body'   => 'Artigo completo',
                        'abstract_html' => 'Resumo',
                        'artigo_doi'    => 'DOI',
                    ];

                    // Se nenhum escopo for enviado, todos são marcados por padrão.
                    $current_scopes = $_GET['search_scope'] ?? array_keys( $search_scopes );

                    foreach ( $search_scopes as $value => $label ) : ?>
                        <div class="search-scope-item">
                            <input type="checkbox" name="search_scope[]" id="scope_<?php echo esc_attr( $value ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $current_scopes, true ) ); ?>>
                            <label for="scope_<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                // Adiciona o link para a busca avançada, se uma página foi configurada.
                $advanced_search_page_id = get_option( 'bjo_advanced_search_page_id', 0 );
                if ( ! empty( $advanced_search_page_id ) ) {
                    $advanced_search_url = get_permalink( $advanced_search_page_id );
                    echo '<p class="advanced-search-link mt-2"><a href="' . esc_url( $advanced_search_url ) . '">Busca Avançada</a></p>';
                }
                ?>
            </div>
 
            <!-- O campo 's' é o campo de busca nativo do WordPress, que usaremos nos bastidores. -->
            <!-- O 's_text' é o que o usuário vê, e seu valor será copiado para 's' via JS se o escopo for o conteúdo. -->
            <input type="hidden" name="s" id="s_native_search" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>">

            <hr>

            <?php foreach ( $taxonomies as $slug => $label ) : ?>
                <?php
                // O slug da taxonomia 'category' é 'category', mas o nosso slug de URL é 'area-de-atuacao'.
                // Precisamos usar o nome correto da taxonomia para buscar os termos.
                $taxonomy_name = ( $slug === 'area-de-atuacao' ) ? 'category' : $slug;
                
                $terms = get_terms( [
                    'taxonomy'   => $taxonomy_name,
                    'hide_empty' => false, // Garante que termos sem posts também apareçam.
                    // FORÇA a busca a ignorar a query principal da página, resolvendo o desaparecimento dos filtros.
                    'update_term_meta_cache' => false,
                ] );

                if ( empty( $terms ) || is_wp_error( $terms ) ) {
                    continue; 
                }

                // O nome do campo no formulário (ex: filter_area_de_atuacao).
                $field_name = 'filter_' . str_replace( '-', '_', $slug );
                $current_selection = $current_filters[ $slug ] ?? []; 
                $has_selection = ! empty( $current_selection );
                ?>
                <div class="filter-group">
                    <label for="<?php echo esc_attr( $field_name ); ?>" class="filter-label"><strong><?php echo esc_html( $label ); ?></strong></label>
                    <select 
                        class="bjo-taxonomy-select" 
                        name="<?php echo esc_attr( $field_name ); ?>[]" 
                        id="<?php echo esc_attr( $field_name ); ?>" 
                        multiple="multiple" 
                        style="width: 100%;"
                        data-placeholder="Selecione um ou mais itens"
                    >
                        <?php foreach ( $terms as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $current_selection ) ); ?>>
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <div class="filter-actions">
                <button type="submit" class="button button-primary">Filtrar</button>
                <a href="<?php echo esc_url( home_url( '/artigos/' ) ); ?>" class="button">Limpar Filtros</a>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();  
}
add_shortcode( 'bjo_filtros_artigos', 'bjo_filtros_artigos_shortcode' );

/**
 * Shortcode para exibir um trecho do conteúdo onde o termo de busca foi encontrado.
 *
 * Uso: [bjo_trecho_busca]
 * Deve ser usado dentro do loop de posts na página de resultados de busca.
 *
 * @return string O HTML com o trecho e o termo destacado.
 */
function bjo_trecho_busca_shortcode() {
	$post_id = get_the_ID();
	// CONDIÇÃO DE GUARDA: Só executa se houver uma busca por texto ativa.
	if ( empty( $_GET['s_text'] ) || ! $post_id || ! function_exists( 'get_field' ) ) {
		return '';
	}
	$search_term = sanitize_text_field( $_GET['s_text'] );
	$output      = '';

	// Pega os escopos da busca da URL para saber onde procurar o trecho.
	$default_scopes = [ 'post_title', 'artigo_body', 'abstract_html', 'artigo_doi' ];
	$selected_scopes = $_GET['search_scope'] ?? $default_scopes;

	// Mapeia os campos para os rótulos que queremos exibir.
	// A busca no título e DOI não gera trecho, mas a lógica está preparada.
	$available_fields = [
		'artigo_body'   => 'no Artigo Completo',
		'abstract_html' => 'no Resumo',
		// 'post_title' e 'artigo_doi' são pesquisados, mas não geram trecho de contexto.
	];

	foreach ( $available_fields as $field_name => $label ) {
		// Só processa o campo se ele foi parte do escopo da busca.
		if ( ! in_array( $field_name, $selected_scopes, true ) ) {
			continue;
		}

		$content = get_field( $field_name, $post_id );

		if ( empty( $content ) ) {
			continue;
		}

		// 1. Remove todas as tags HTML para evitar quebras e fazer uma busca limpa.
		$clean_content = wp_strip_all_tags( $content );
		// 2. Remove quebras de linha excessivas para um texto mais fluido.
		$clean_content = preg_replace( '/\s+/', ' ', $clean_content );

		// 3. Procura a primeira ocorrência do termo (case-insensitive).
		$pos = stripos( $clean_content, $search_term );

		if ( $pos !== false ) {
			// 4. Extrai um trecho ao redor do termo encontrado.
			$start   = max( 0, $pos - 50 ); // Pega um pouco antes.
			$length  = strlen( $search_term ) + 100; // Pega o termo e um pouco depois.
			$snippet = substr( $clean_content, $start, $length );

			// Adiciona "..." se o trecho não começar no início do texto.
			if ( $start > 0 ) {
				$snippet = '... ' . $snippet;
			}
			// Adiciona "..." se o trecho não terminar no final do texto.
			if ( ( $start + $length ) < strlen( $clean_content ) ) {
				$snippet .= ' ...';
			}

			// 5. Destaca TODAS as ocorrências do termo no trecho (case-insensitive).
			$highlighted_snippet = preg_replace(
				'/' . preg_quote( $search_term, '/' ) . '/i',
				'<strong>$0</strong>',
				esc_html( $snippet )
			);

			// 6. Monta o HTML de saída.
			$output .= '<p class="search-excerpt-context">';
			$output .= '<small><em>Encontrado ' . esc_html( $label ) . ': ' . $highlighted_snippet . '</em></small>';
			$output .= '</p>';
		}
	}

	return $output;
}
add_shortcode( 'bjo_trecho_busca', 'bjo_trecho_busca_shortcode' );
