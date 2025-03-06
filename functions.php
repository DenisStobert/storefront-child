<?php
// Enqueue parent theme styles
function storefront_child_enqueue_styles() {
    // Enqueue the parent theme's style.css
    wp_enqueue_style('storefront-parent-style', get_template_directory_uri() . '/style.css');

    // Enqueue the child theme's style.css
    wp_enqueue_style('storefront-child-style', get_stylesheet_directory_uri() . '/style.css', array('storefront-parent-style'));
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

// Create custom post type "Cities"
function create_cities_post_type() {
    $args = array(
        'labels' => array(
            'name'          => 'Cities', // Plural name
            'singular_name' => 'City', // Singular name
            'add_new'       => 'Add New City', // Add new city button
            'add_new_item'  => 'Add New City', // Add new city item
            'edit_item'     => 'Edit City', // Edit city button
            'new_item'      => 'New City', // New city item
            'view_item'     => 'View City', // View city button
            'search_items'  => 'Search Cities', // Search for cities
            'not_found'     => 'No Cities found', // No cities found message
            'not_found_in_trash' => 'No Cities found in Trash', // No cities in trash message
        ),
        'public'        => true, // Make the custom post type public
        'has_archive'   => true, // Enable archive for custom post type
        'supports'      => array('title', 'editor', 'thumbnail'), // Supported features for custom post type
        'menu_icon'     => 'dashicons-location', // Menu icon for custom post type in admin
    );
    // Register the custom post type "cities"
    register_post_type('cities', $args);
}
add_action('init', 'create_cities_post_type');

// Add a meta box for entering latitude and longitude
function add_cities_meta_box() {
    add_meta_box(
        'city_location', 
        'City Location', // Meta box title
        'render_cities_meta_box', // Function to render HTML for the meta box
        'cities', // Post type where the meta box should appear
        'normal', // Position of the meta box
        'default' // Priority of the meta box
    );
}
add_action('add_meta_boxes', 'add_cities_meta_box');

// Render HTML for the city location meta box
function render_cities_meta_box($post) {
    // Retrieve the saved latitude and longitude values
    $latitude = get_post_meta($post->ID, 'latitude', true);
    $longitude = get_post_meta($post->ID, 'longitude', true);

    ?>
    <p>
        <label for="latitude">Latitude:</label>
        <input type="text" name="latitude" id="latitude" value="<?php echo esc_attr($latitude); ?>" />
    </p>
    <p>
        <label for="longitude">Longitude:</label>
        <input type="text" name="longitude" id="longitude" value="<?php echo esc_attr($longitude); ?>" />
    </p>
    <?php
}

// Save the latitude and longitude values when the post is saved
function save_cities_meta_box($post_id) {
    // Check if latitude and longitude values are set
    if (array_key_exists('latitude', $_POST)) {
        update_post_meta($post_id, 'latitude', sanitize_text_field($_POST['latitude']));
    }
    if (array_key_exists('longitude', $_POST)) {
        update_post_meta($post_id, 'longitude', sanitize_text_field($_POST['longitude']));
    }
}
add_action('save_post', 'save_cities_meta_box');

// Create a custom taxonomy "Countries"
function create_countries_taxonomy() {
    $args = array(
        'labels' => array(
            'name'          => 'Countries', // Plural name
            'singular_name' => 'Country', // Singular name
            'search_items'  => 'Search Countries', // Search for countries
            'all_items'     => 'All Countries', // View all countries
            'edit_item'     => 'Edit Country', // Edit country button
            'update_item'   => 'Update Country', // Update country button
            'add_new_item'  => 'Add New Country', // Add new country button
            'new_item_name' => 'New Country Name', // New country name
            'menu_name'     => 'Countries', // Menu name in admin
        ),
        'hierarchical' => true, // Hierarchical taxonomy (like categories)
        'public'       => true, // Make the taxonomy public
        'show_admin_column' => true, // Show the taxonomy column in the admin
    );
    // Register the custom taxonomy "countries" for the "cities" post type
    register_taxonomy('countries', 'cities', $args);
}
add_action('init', 'create_countries_taxonomy');

// Weather widget for displaying city weather
class City_Weather_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'city_weather_widget',
            'City Weather Widget',
            array('description' => 'Displays the current weather of a city') // Widget description
        );
    }

    // Display the widget content
    public function widget($args, $instance) {
        // Get the selected city ID from widget settings
        $city_id = isset($instance['city_id']) ? $instance['city_id'] : '';
        $city = get_post($city_id);

        if ($city) {
            // Retrieve the city name, latitude, and longitude
            $city_name = $city->post_title;
            $latitude = get_post_meta($city_id, 'latitude', true);
            $longitude = get_post_meta($city_id, 'longitude', true);

            // Get the weather data from OpenWeatherMap API
            $api_key = '08dee94192fe52f88bf26c96272cb7d8'; // Your API key
            $weather_data = file_get_contents("http://api.openweathermap.org/data/2.5/weather?lat=$latitude&lon=$longitude&appid=$api_key&units=metric");
            $weather = json_decode($weather_data);

            // If weather data is available, display the temperature
            if ($weather && isset($weather->main->temp)) {
                $temperature = $weather->main->temp;
            } else {
                $temperature = 'N/A';
            }

            // Output the widget content
            echo $args['before_widget'];
            echo $args['before_title'] . $city_name . $args['after_title'];
            echo '<p>Temperature: ' . $temperature . ' °C</p>';
            echo $args['after_widget'];
        }
    }

    // Widget settings form
    public function form($instance) {
        $city_id = isset($instance['city_id']) ? $instance['city_id'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('city_id'); ?>">Select City:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('city_id'); ?>" name="<?php echo $this->get_field_name('city_id'); ?>">
                <?php
                // Get all cities and display them in a dropdown list
                $cities = get_posts(array('post_type' => 'cities', 'posts_per_page' => -1));
                foreach ($cities as $city) {
                    $selected = ($city->ID == $city_id) ? 'selected' : '';
                    echo '<option value="' . $city->ID . '" ' . $selected . '>' . $city->post_title . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }

    // Save the widget settings
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        // Save the selected city ID
        $instance['city_id'] = !empty($new_instance['city_id']) ? sanitize_text_field($new_instance['city_id']) : '';
        return $instance;
    }
}

// Register the city weather widget
function register_city_weather_widget() {
    register_widget('City_Weather_Widget');
}
add_action('widgets_init', 'register_city_weather_widget');

// Custom template for displaying the list of countries, cities, and temperatures
function custom_page_template($template) {
    // If this is the "countries-cities" page, use a custom template
    if (is_page('countries-cities')) {
        $template = locate_template('custom-countries-cities.php');
    }
    return $template;
}
add_filter('template_include', 'custom_page_template');

// Query to get country, city, and temperature data
function get_countries_cities_data() {
    global $wpdb;
    $sql = "
        SELECT p.ID, p.post_title AS city, t.name AS country, pm_lat.meta_value AS latitude, pm_lon.meta_value AS longitude
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = 'latitude'
        LEFT JOIN {$wpdb->postmeta} pm_lon ON p.ID = pm_lon.post_id AND pm_lon.meta_key = 'longitude'
        WHERE p.post_type = 'cities' AND tt.taxonomy = 'countries'
        ORDER BY t.name, p.post_title
    ";

    $results = $wpdb->get_results($sql);

    return $results;
}

// Display the country, city, and temperature table
function display_countries_cities_table() {
    $data = get_countries_cities_data();
    if ($data) {
        // Display the table before the content
        do_action('before_countries_cities_table'); // Хук до таблицы

        echo '<table>';
        echo '<tr><th>Country</th><th>City</th><th>Temperature (°C)</th></tr>';
        foreach ($data as $row) {
            $city_name = $row->city;
            $country_name = $row->country;
            $latitude = $row->latitude;
            $longitude = $row->longitude;

            // Get the weather data
            $api_key = '08dee94192fe52f88bf26c96272cb7d8';
            $weather_data = file_get_contents("http://api.openweathermap.org/data/2.5/weather?lat=$latitude&lon=$longitude&appid=$api_key&units=metric");
            $weather = json_decode($weather_data);

            $temperature = $weather && isset($weather->main->temp) ? $weather->main->temp : 'N/A';

            echo '<tr>';
            echo '<td>' . esc_html($country_name) . '</td>';
            echo '<td>' . esc_html($city_name) . '</td>';
            echo '<td>' . esc_html($temperature) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Display the table after the content
        do_action('after_countries_cities_table'); // Хук после таблицы
    } else {
        echo '<p>No cities found.</p>';
    }
}

// Function to display content before the table  
function custom_before_countries_cities_table() {  
    echo '<p>List of cities with temperature and additional information:</p>';  
    echo '<p>We retrieve temperature data using the OpenWeather API for each city.</p>';  
}  
add_action('before_countries_cities_table', 'custom_before_countries_cities_table');  

// Function to display content after the table  
function custom_after_countries_cities_table() {  
    echo '<p>This is the end of the list of cities with temperature data. We hope you found the information useful!</p>';  
    echo '<a href="https://www.openweathermap.org" target="_blank">Learn more about the weather on OpenWeather</a>';  
}  

add_action('after_countries_cities_table', 'custom_after_countries_cities_table');

// Display the countries and cities table in the footer of the website
add_action('wp_footer', 'display_countries_cities_table'); // Where the table will be output

// Enqueue scripts and styles for AJAX-based city search
function enqueue_city_search_scripts() {
    // Enqueue the JavaScript file for AJAX search
    wp_enqueue_script('city-search-ajax', get_stylesheet_directory_uri() . '/js/city-search-ajax.js', array('jquery'), null, true);
    
    // Localize the script to pass the AJAX URL as a JavaScript variable
    wp_localize_script('city-search-ajax', 'citySearchAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'), // Pass the admin-ajax.php URL for AJAX requests
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_city_search_scripts');

// AJAX request handler for city search functionality
function city_search_ajax() {
    global $wpdb;

    // Get the search term (sanitized) from the AJAX request
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // SQL query to retrieve city data based on the search term
    $sql = "
        SELECT p.ID, p.post_title AS city, t.name AS country, pm_lat.meta_value AS latitude, pm_lon.meta_value AS longitude
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->prefix}term_relationships tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = 'latitude'
        LEFT JOIN {$wpdb->postmeta} pm_lon ON p.ID = pm_lon.post_id AND pm_lon.meta_key = 'longitude'
        WHERE p.post_type = 'cities' AND tt.taxonomy = 'countries' AND p.post_title LIKE %s
    ";

    // Execute the SQL query, using the search term in the LIKE condition
    $cities = $wpdb->get_results($wpdb->prepare($sql, '%' . $wpdb->esc_like($search) . '%'));

    // Send back the search results as a JSON response
    wp_send_json_success($cities);
}

// Register the AJAX actions for both logged-in and non-logged-in users
add_action('wp_ajax_city_search', 'city_search_ajax'); // For logged-in users
add_action('wp_ajax_nopriv_city_search', 'city_search_ajax'); // For non-logged-in users