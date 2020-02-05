(function($) {

  $.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
      .exec(window.location.search);

    return (results !== null) ? results[1] || 0 : false;
  };

  $(document).ready(function() {
    var $form = $('form.CRM_Admin_Form_DonrecProfile');
    var $variablesWrapper = $form.find('#crm-donrec-profile-form-block-variables-wrapper');
    $variablesWrapper
      .css('position', 'relative')
      .append(
        $('<div>')
          .hide()
          .addClass('loading-overlay')
          .css({
            backgroundColor: 'rgba(255, 255, 255, 0.5)',
            position: 'absolute',
            top: 0,
            right: 0,
            bottom: 0,
            left: 0
          })
          .append(
            $('<div>')
              .addClass('crm-loading-element')
              .css({
                position: 'absolute',
                left: '50%',
                top: '50%',
                marginLeft: '-15px',
                marginTop: '-15px'
              })
          )
      );
    $('#variables_more')
      .on('click', function() {
        var urlSearchparams = new URLSearchParams(window.location.search);
        urlSearchparams.append('ajax_action', 'add_variable');
        var postValues = {
          qfKey: $form.find('[name="qfKey"]').val(),
          ajax_action: 'add_variable',
          snippet: 6
        };
        var $currentVariables = $form.find('[name^="variables--"]');
        $currentVariables.each(function() {
          postValues[$(this).attr('name')] = $(this).val();
        });

        $variablesWrapper.find('.loading-overlay').show();

        // Retrieve the form with another variable field.
        $.post(
          CRM.url(
            'civicrm/admin/setting/donrec/profile',
            'op=' + $.urlParam('op') + '&id=' + $.urlParam('id')
          ),
          postValues,
          function(data) {
            $variablesWrapper
              .find('.crm-donrec-profile-form-block-variables-table tbody')
              .append($(data.content)
                .find('#crm-donrec-profile-form-block-variables-wrapper tr.crm-donrec-profile-form-block-variable').last()
              );
            $variablesWrapper.find('.loading-overlay').hide();
          }
        );
      });
  });

})(CRM.$ || cj);
