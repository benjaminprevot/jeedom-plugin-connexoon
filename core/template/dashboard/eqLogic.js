(function() {
  'use strict';

  $.fn.connexoon = function() {
    return this.each(function() {
      var $connexoon = $(this);
      var $value = $('.value__display', $connexoon);
      var $shutter = $('.shutter__display', $connexoon);
      var $slider = $('.slider', $connexoon);
      var $confirmation = $('.connexoon__confirmation', $connexoon);
      var $confirmationCancel = $('.confirmation__commands .commands__command.commands__command--cancel', $confirmation);
      var $confirmationValidate = $('.confirmation__commands .commands__command.commands__command--validate', $confirmation);

      var positionCommandId = $(this).data('position_cmd_id');

      var display = function(value) {
        $value.text(value + '%');
        $shutter.height(value + '%');
      }

      var save = function(value) {
        $connexoon.data('value', value);
        $slider.slider('value', 100 - value);
      }

      var refresh = function() {
        jeedom.cmd.execute({
          id: positionCommandId,
          success: function(position) {
            if (position != $connexoon.data('value')) {
              save(position);
            }

            setTimeout(refresh, 300000);
          }
      });
      }

      $slider.slider({
        orientation: 'vertical',
        min: 0,
        max: 100,
        value: 100 - $connexoon.data('value'),
        create: function(event, ui) {
          display($connexoon.data('value'));
        },
        slide: function(event, ui) {
          display(100 - ui.value);
        },
        change: function(event, ui) {
          display(100 - ui.value);
        },
        stop: function(event, ui) {
          $confirmation.addClass('connexoon__confirmation--active');
        }
      });

      $confirmationCancel
          .on('click', function() {
            $slider.slider('value', 100 - $connexoon.data('value'));
            $confirmation.removeClass('connexoon__confirmation--active');
          });

      $confirmationValidate
          .on('click', function() {
            jeedom.cmd.execute({
              id: $(this).data('cmd_id'),
              value: {
                slider: (100 - $slider.slider('value'))
              },
              success: function() {
                save(100 - $slider.slider('value'));
                $confirmation.removeClass('connexoon__confirmation--active');
              }
            });
          });

      refresh();
    });
  };

  $(document).on('click', '.connexoon .connexoon__commands .commands__command', function() {
    jeedom.cmd.execute({
        id: $(this).data('cmd_id')
    });
  });
})(jQuery);
