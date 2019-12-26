(function() {
    'use strict';

    $(document).on('click', '.connexoon-eqLogic .cmd', function() {
        jeedom.cmd.execute({
            id: $(this).data('cmd_id')
        });
    });
})(jQuery);
