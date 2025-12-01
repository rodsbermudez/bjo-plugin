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

