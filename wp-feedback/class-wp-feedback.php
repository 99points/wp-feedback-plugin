<?php
class WP_Feedback {

    /**
     * Constructor
     */
    public function __construct() {

        // Add hooks and actions here
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'display_voting_feature'));
        add_action('wp_ajax_submit_vote', array($this, 'submit_vote'));
        add_action('wp_ajax_nopriv_submit_vote', array($this, 'submit_vote'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
    }

    /**
     * Enqueue JavaScript and CSS files for voting functionality
    */
    
    public function enqueue_scripts() {
        // Enqueue JavaScript and CSS files for voting functionality
        wp_enqueue_script('wp-feedback', plugin_dir_url(__FILE__) . 'js/wp-feedback.js', array('jquery'), '1.0', true);
        wp_enqueue_style('wp-feedback', plugin_dir_url(__FILE__) . 'css/wp-feedback.css');
    }

    /**
     * Display the voting feature at the end of single post articles.
     *
     * @param string $content The post content.
     * @return string The modified post content with the voting feature.
     */
    public function display_voting_feature($content) {
        // Check if it's a single post
        if (is_single()) {
            // Get the post ID
            $post_id = get_the_ID();

            // Get the current voting data for this post (you'll need to implement this part)
            $voting_data = $this->get_voting_data($post_id);
            $cssYes = $cssNo = '';
            // Check if the user has already voted (you'll need to implement this part)
            $has_voted = $this->user_has_voted($post_id);
            
            // HTML markup for the voting feature
            $voting_markup = '<div class="wp-feedback-container">';

            // Check if the user has already voted
            if (!$has_voted) {
                $voting_markup .= '<div class="wp-feedback-buttons"><h4>WAS THIS ARTICLE HELPFUL?</h4>';
                $voting_markup .= '<button class="wp-feedback-button" data-post-id="'.$post_id.'" data-vote="yes"><span>&#9787;</span> <em>Yes</em></button>';
                $voting_markup .= '<button class="wp-feedback-button" data-post-id="'.$post_id.'" data-vote="no"><span>&#9865;</span> <em>No</em></button>';
            } 
            else {
                $result = $this->get_voting_percent($voting_data);

                $vote = get_post_meta($post_id, 'wp_feedback_user_voted', true);

                if ($vote === 'yes') {
                    $cssYes = 'wp_feedback_voted';
                } elseif ($vote === 'no') {
                    $cssNo = 'wp_feedback_voted';
                }

                $voting_markup .= '<div class="wp-feedback-buttons wp-feedback-result"><h4>THANK YOU FOR VOUR FEEDBACK.</h4>';
                $voting_markup .= '<div class="wp-feedback-button '.esc_attr($cssYes).'"><span>☻</span><em>'.$result['yes_percentage'].'</em></div>';
                $voting_markup .= '<div class="wp-feedback-button '.esc_attr($cssNo).'"><span>⚉</span><em>'.$result['no_percentage'].'</em></div>';
            }

            $voting_markup .= '</div>';

            $voting_markup .= '</div>';
    
            // Append the voting feature to the content
            $content .= $voting_markup;
        }
        return $content;
    }

    /**
     * Get the voting data for a specific post.
     *
     * @param int $post_id The post ID for which to retrieve voting data.
     * @return array An array containing voting data with 'yes' and 'no' counts.
     */

    public function get_voting_data($post_id) {
        $voting_data = get_post_meta($post_id, 'wp_feedback_voting_data', true);
    
        // If there's no existing voting data, initialize it
        if (!$voting_data) {
            $voting_data = array(
                'yes' => 0,
                'no' => 0,
            );
        }
    
        return $voting_data;
    }
    
    /**
     * Check if the current user has already voted for the given post.
     *
     * @param int $post_id The post ID to check for.
     * @return bool True if the user has already voted, false otherwise.
     */
    public function user_has_voted($post_id) {
        $cookie_name = 'wp_feedback_vote_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            return true;
        }
        else{
            $vote = get_post_meta($post_id, 'wp_feedback_user_voted', true);
            if(isset($vote))
                return true;
            else
                return false;
        }
    }
    
    /**
     * Handle the submission of votes
     */
    public function submit_vote() {
        // Verify the AJAX nonce for security
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp-feedback-nonce')) {
            // Get the post ID and vote value from the AJAX request
            $post_id = absint($_POST['post_id']);
            $vote = sanitize_text_field($_POST['vote']);
    
            // Check if the user has already voted
            if ($this->user_has_voted($post_id)) {
                echo json_encode(array('error' => 'You have already voted.'));
            } else {
                // Record the vote
                $voting_data = $this->get_voting_data($post_id);
                $cssYes = $cssNo = '';
                if ($vote === 'yes') {
                    $voting_data['yes']++;
                    $cssYes = 'wp_feedback_voted';
                } elseif ($vote === 'no') {
                    $voting_data['no']++;
                    $cssNo = 'wp_feedback_voted';
                }
                // Update the voting data in your database or storage method
                // For this example, we'll use post meta
                update_post_meta($post_id, 'wp_feedback_voting_data', $voting_data);
                
                // Save the user's choice (Yes or No) in a user-specific option
                update_post_meta($post_id, 'wp_feedback_user_voted', $vote);

                $result = $this->get_voting_percent($voting_data);

                // Set a cookie to mark that the user has voted
                $cookie_name = 'wp_feedback_vote_' . $post_id;
                setcookie($cookie_name, 'voted', time() + 31536000, COOKIEPATH, COOKIE_DOMAIN);

                $response = '<h4>THANK YOU FOR VOUR FEEDBACK.</h4>';
                $response .= '<div class="wp-feedback-button '.esc_attr($cssYes).'"><span>☻</span><em>'.$result['yes_percentage'].'</em></div>';
                $response .= '<div class="wp-feedback-button '.esc_attr($cssNo).'"><span>⚉</span><em>'.$result['no_percentage'].'</em></div>';

                echo json_encode(array('success' => 'Vote recorded.', 'response' => $response));
            }
        } else {
            echo json_encode(array('error' => 'Invalid nonce.'));
        }
        // Always exit to prevent extra output
        exit();
    }

    /**
     * Get the voting percentages based on the voting data.
     *
     * @param array $voting_data The voting data for the post.
     * @return array An array containing 'yes_percentage' and 'no_percentage'.
     */

    public function get_voting_percent($voting_data){
        // Calculate the percentages
        $total_votes = $voting_data['yes'] + $voting_data['no'];
        $yes_percentage = ($total_votes > 0) ? round(($voting_data['yes'] / $total_votes) * 100) : 0;
        $no_percentage = 100 - $yes_percentage;
        // Escape the percentage values
        $yes_percentage_escaped = esc_html($yes_percentage . '%');
        $no_percentage_escaped = esc_html($no_percentage . '%');

        // Return the percentages as an array
        return array(
            'yes_percentage' => $yes_percentage_escaped,
            'no_percentage' => $no_percentage_escaped,
        );
    }
    /**
     * Add a meta box to display voting results when editing articles
     */
    public function add_meta_box() {
        add_meta_box(
            'wp-feedback-meta-box',
            'Voting Results',
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }
    
    /**
     * Render the meta box content to display voting results when editing articles.
     *
     * @param WP_Post $post The post object.
     */

    public function render_meta_box($post) {
        // Retrieve voting data for the current post
        $voting_data = $this->get_voting_data($post->ID);
    
        // Get voting percentages
        $percentages = $this->get_voting_percent($voting_data);
    
        // Display the meta box content
        echo '<p><strong>Yes Percentage:</strong> ' . $percentages['yes_percentage'] . '</p>';
        echo '<p><strong>No Percentage:</strong> ' . $percentages['no_percentage'] . '</p>';
    }
    
}
