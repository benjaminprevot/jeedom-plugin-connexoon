(function() {
  'use strict';

  $.fn.connexoon = function() {
    return this.each(function() {
      var $connexoon = $(this);
      var $value = $('.value__display', $connexoon);
      var $shutter = $('.shutter__display', $connexoon);
      var $slider = $('.slider', $connexoon);
      var $confirmation = $('.connexoon__confirmation', $connexoon);
      var $confirmationMessage = $('.confirmation__message .message__value', $connexoon);
      var $confirmationCancel = $('.confirmation__commands .commands__command.commands__command--cancel', $confirmation);
      var $confirmationValidate = $('.confirmation__commands .commands__command.commands__command--validate', $confirmation);

      var positionCommandId = $(this).data('position_cmd_id');
      var valueReversed = $(this).data('value_reversed');

      var display = function(value) {
        $value.text(value + '%');
        $shutter.height(((1 - 2 * valueReversed) * value + 100 * valueReversed) + '%');
      }

      var save = function(value) {
        $connexoon.data('value', value);
        $slider.slider('value', 100 * (1 - valueReversed) - (1 - 2 * valueReversed) * value);
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
        value: 100 * (1 - valueReversed) - (1 - 2 * valueReversed) * $connexoon.data('value'),
        create: function(event, ui) {
          display($connexoon.data('value'));
        },
        slide: function(event, ui) {
          display(100 * (1 - valueReversed) - (1 - 2 * valueReversed) * ui.value);
        },
        change: function(event, ui) {
          display(100 * (1 - valueReversed) - (1 - 2 * valueReversed) * ui.value);
        },
        stop: function(event, ui) {
          $confirmationMessage.text((100 * (1 - valueReversed) - (1 - 2 * valueReversed) * ui.value) + '%');
          $confirmation.addClass('connexoon__confirmation--active');
        }
      });

      $confirmationCancel
          .on('click', function() {
            $slider.slider('value', 100 * (1 - valueReversed) - (1 - 2 * valueReversed) * $connexoon.data('value'));
            $confirmation.removeClass('connexoon__confirmation--active');
          });

      $confirmationValidate
          .on('click', function() {
            var newPosition = 100 * (1 - valueReversed) - (1 - 2 * valueReversed) * $slider.slider('value');
            
            jeedom.cmd.execute({
              id: $(this).data('cmd_id'),
              value: {
                slider: newPosition
              },
              success: function() {
                save(newPosition);
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
