jQuery(document).ready(function ($) {
    // Itera sobre cada botão de toggle que criamos.
    $('.filter-toggle-button').each(function () {
        const $button = $(this);
        const $filterGroup = $button.closest('.filter-group');

        function updateButtonText() {
            if ($filterGroup.hasClass('is-open')) {
                $button.text('Ver menos');
            } else if ($filterGroup.hasClass('has-selection')) {
                $button.text('Ver mais');
            } else {
                $button.text('Ver todos');
            }
        }

        // Define o estado inicial e o texto do botão na carga da página. 
        updateButtonText();

        $button.on('click', function (e) {
            e.preventDefault();
            console.log('Botão de filtro clicado!', this); // <-- DEBUG ADICIONADO AQUI
            $filterGroup.toggleClass('is-open');
            updateButtonText();
        });
    });
}); 