<?php
/**
 * Funções relacionadas a taxonomias e shortcodes.
 *
 * @package BJO_Plugin
 */

// Impede o acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cria um shortcode para listar os termos de uma taxonomia específica.
 *
 * O shortcode aceita o parâmetro 'taxonomia' para definir de qual taxonomia
 * os termos serão listados.
 * Exemplo de uso: [listar_termos taxonomia="autor"]
 *
 * @param array $atts Atributos do shortcode.
 * @return string O HTML da lista de termos ou uma string vazia.
 */
function patropi_bjo_listar_termos_shortcode( $atts ) {
	// Define os atributos padrão e mescla com os atributos passados.
	$atts = shortcode_atts(
		array(
			'taxonomia' => '', // Valor padrão vazio.
		),
		$atts,
		'listar_termos'
	);

	$taxonomy_slug = sanitize_text_field( $atts['taxonomia'] );

	// Se nenhuma taxonomia for especificada, não faz nada.
	if ( empty( $taxonomy_slug ) ) {
		return '';
	}

	$terms = get_terms( array(
		'taxonomy'   => $taxonomy_slug,
		'hide_empty' => true,
	) );

	// Se não encontrar termos ou der erro, retorna vazio.
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	// Inicia a captura do output para retornar como string.
	ob_start();
	?>
	<ul class="lista-de-termos lista-tax-<?php echo esc_attr( $taxonomy_slug ); ?>">
		<?php foreach ( $terms as $term ) : ?>
			<?php $term_link = get_term_link( $term ); ?>
			<?php if ( ! is_wp_error( $term_link ) ) : ?>
				<li><a href="<?php echo esc_url( $term_link ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<?php
	// Retorna o conteúdo capturado.
	return ob_get_clean();
}
add_shortcode( 'listar_termos', 'patropi_bjo_listar_termos_shortcode' );