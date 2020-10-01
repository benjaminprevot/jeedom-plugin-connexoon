(function() {
  'use strict';

  $(document).on('click', '.connexoon .connexoon__commands .commands__command', function() {
    jeedom.cmd.execute({
        id: $(this).data('cmd_id')
    });
  });
})(jQuery);
