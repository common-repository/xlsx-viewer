// TABLE SETTING
function save_table_setup_settings()
{
    jQuery(document).ready(function($) 
    {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'save_settings',

                // Retrieve input values from the form
                post_id : $('#post_id').val(),
                border_show : $('#border_show').val(),
                border_color : $('#border_color').val(),
                first_row_show : $('#first_row_show').val(),
                first_row_text_color : $('#first_row_text_color').val(),
                first_row_background_color : $('#first_row_background_color').val(),
                table_background_color : $('#table_background_color').val()
            },
    
            success: function(response) 
            {
                location.reload();
                // console.log(response);
            },
    
            error: function(error) 
            {
                console.log(error);
            }
        });
    });
}

jQuery(document).ready(function()
{
    jQuery('.accordion').click(function()
    {
        var panel = jQuery(this).next();
        var panels = this.nextElementSibling;

        if (panels.style.display === "block") 
        {
            this.innerHTML = '<i class="fa fa-arrow-down" aria-hidden="true"></i> Open table setting';
        } else {
            this.innerHTML = '<i class="fa fa-arrow-up" aria-hidden="true"></i> Close table setting';
        }

        panel.slideToggle();
    });
});

// FILE MANAGEMENT
function delete_file(rnd)
{
    jQuery(document).ready(function($) 
    {
        var message = jQuery.trim(jQuery("#delete_file_" + rnd).val());
        var answer = confirm('Are you sure you want to delete "' + message + '"?');

        if (answer) 
        {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'delete_file',
                    filename : message,
                },
                dataType: "text",
        
                success: function(response) 
                {
                    location.reload();
                },
        
                error: function(error) 
                {
                    console.log(error);
                }
            });
        }
     });
}
