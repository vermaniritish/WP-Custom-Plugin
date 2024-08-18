jQuery(document).ready(function($) {
    $('#upload_form').on('submit', function(e) {
        e.preventDefault();
      
        if(!$('#upload_form [type=file]').val()) return false;
  
        $('#upload_form [type=submit]').prop('disabled', true).val('Processing...')
        var formData = new FormData(this);
  
        $.ajax({
            url: bev_ajax_object.ajax_url + '?action=bev_upload_csv',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('File upload failed: ' + error);
            }
        });
    });
  
    $('#select_column_form').on('submit', function(e) {
        e.preventDefault();
  
        var csvFile = $('#csv_file').val();
        var emailColumn = $('input[name="email_column"]:checked').val();
          if(!emailColumn) return false;
        $('#column_selector').hide();
        $('#verification_progress').show();
        $('#progress_status').text('Verifying emails...');
  
        $.ajax({
            url: bev_ajax_object.ajax_url + '?action=bev_verify_emails',
            type: 'POST',
            data: { csv_file: csvFile, email_column: emailColumn },
            success: function(response) {
                if (response && response.success) {
                    window.location.reload();
                } else {
                    alert('Email verification failed: ' + response);
                }
            },
            error: function(xhr, status, error) {
                alert('Email verification failed: ' + error);
            }
        });
    });
  
    $('.view-report').on('click', function() {
        showViewModal($(this).attr('data-href'));
    });

    function showViewModal(link)
    {
        $('#view-modal').show()
        $('#view-modal .modal-body').html('<p style="padding: 20px;">Loading...</p>');
        $.ajax({
            url: link,
            success: function(resp) {
                $('#view-modal .modal-body').html(resp);
            }
        });
    }

    var saving = false;
    $('#manual-sync-modal form').on('submit', function(e) {
        e.preventDefault();
        if(saving) return false;
        saving = true;
        $('#manual-sync-modal .error-message').hide();
        $.ajax({
            url: $('#manual-sync-modal form').attr('action'),
            data: $('#manual-sync-modal form').serialize(),
            success: function(resp) {
                if(resp)
                {
                    console.log(resp);
                    resp = JSON.parse(resp);
                    if(resp && resp.status)
                    {
                        $('#manual-sync-modal').hide();
                        if(resp.link)
                        showViewModal(resp.link)
                    }
                    else if(resp && resp.error)
                    {
                        $('#manual-sync-modal .error-message').show().html(resp.error)
                    }
                }
                else
                {
                    $('#manual-sync-modal .error-message p').show().html('Something went wrong. Please try again.')
                }
            },
            complete: function() {
                saving = false;
            }
        })
    })

    $('#manual-sync').on('click', function() {
        $('#manual-sync-modal .error-message').hide();
        $('#manual-sync-modal input').val('');
        $('#manual-sync-modal').show()
    });
  
    $('body').on('click', '.close-view-modal', function() {
      $('#view-modal').hide();
      $('#manual-sync-modal').hide()
    });
  
    if($('#MVTable').length > 0) {
        let table = new DataTable('#MVTable', {order: [[1, 'desc']]});
    }
  });