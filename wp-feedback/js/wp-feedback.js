jQuery(document).ready(function ($) {
    $('button.wp-feedback-button').on('click', function () {
        var button = $(this);
        var postID = $(this).data('post-id');
        var vote = $(this).data('vote');
        var nonce = wp_feedback_vars.nonce;

        // Show the loader
        button.addClass('loading');
       
        $.ajax({
            type: 'POST',
            url: wp_feedback_vars.ajax_url,
            data: {
                action: 'submit_vote',
                post_id: postID,
                vote: vote,
                nonce: nonce
            },
            success: function (response) {
                var result = $.parseJSON(response);
                
                if (result.success) {
                    // Vote was recorded successfully, update UI as needed
                    // alert('Thank you for your vote!');
                    $('.wp-feedback-buttons').html(result.response);
                    $('.wp-feedback-buttons').addClass('wp-feedback-result');
                    // Disable the buttons or update the UI as per your design
                } else if (result.error) {
                    // Handle the error (e.g., user already voted)
                    alert(result.error);
                }
            },
            complete: function () {
                // Hide the loader
                button.removeClass('loading');
            }
        });
    });
});
