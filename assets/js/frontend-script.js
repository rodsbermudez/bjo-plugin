console.log('BJO DEBUG: frontend-script.js carregado.');

jQuery(document).ready(function ($) {
	console.log('BJO DEBUG: Documento pronto (document.ready).');

	function inicializarSelect2() {
		console.log('BJO DEBUG: Tentando inicializar o Select2...');
		var $selects = $('.bjo-taxonomy-select');
		console.log('BJO DEBUG: Elementos encontrados com a classe ".bjo-taxonomy-select": ' + $selects.length);

		if ($selects.length > 0 && $.fn.select2) {
			console.log('BJO DEBUG: Aplicando Select2 em ' + $selects.length + ' elemento(s).');
			$selects.select2({
				// language: "pt-BR"
			});
		} else if ($selects.length === 0) {
			console.log('BJO DEBUG: Nenhum elemento encontrado para aplicar o Select2.');
		} else {
			console.log('BJO DEBUG: A função $.fn.select2 não está disponível.');
		}
	}

	// Tenta inicializar quando o Elementor termina de carregar o frontend.
	$(window).on('elementor/frontend/init', function () {
		console.log('BJO DEBUG: Evento "elementor/frontend/init" disparado.');
		inicializarSelect2();
	});

	// Como fallback, tenta inicializar após um pequeno atraso depois que a página inteira carregar.
	// Às vezes, isso funciona se o hook do Elementor não for confiável para um widget específico.
	setTimeout(inicializarSelect2, 1000);
});
